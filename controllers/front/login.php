<?php

use Doctrine\ORM\EntityManagerInterface;
use JustAuthMe\JustAuthMe\Entity\JamUser;
use JustAuthMe\SDK\JamSdk;

class justauthmeloginModuleFrontController extends ModuleFrontController {
    private $app_id;
    private $redirect_url;
    private $secret;
    private $error;

    public function __construct()
    {
        parent::__construct();

        $this->app_id = Configuration::get(JustAuthMe::CONFIG_APP_ID);
        $this->redirect_url = Configuration::get(JustAuthMe::CONFIG_CALLBACK_URL);
        $this->secret = Configuration::get(JustAuthMe::CONFIG_API_SECRET);
    }


    public function initContent(): void
    {
        parent::initContent();

        if (isset($_GET['access_token'])) {
            if ($this->handleJamLogin($_GET['access_token'])) {
                // Everything is OK
                // TODO: Redirect to home
            } else {
                // Something went wrong
                // TODO: log and register error
            }
        }

        $this->setTemplate('module:justauthme/views/templates/front/login.tpl');
    }

    private function handleJamLogin(string $access_token): bool
    {
        $jamSdk = new JamSdk(
            $this->app_id,
            $this->redirect_url,
            $this->secret
        );

        try {
            $apiResponse = $jamSdk->getUserInfos($access_token);
        } catch (Exception $e) {
            // Fail getting data
            error_log('JustAuthMe module error: ' . $e->getMessage());
            $this->error = $this->l('You do not exists in our customer database. Please remove the "' . Configuration::get('PS_SHOP_NAME') . '" Service from your JustAuthme app and try again.');

            return false;
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jamUserRepository = $entityManager->getRepository(JamUser::class);

        if (isset($apiResponse->email)) {

            // First login using JAM
            if (!Customer::customerExists($apiResponse->email)) {

                // User does not exists, do registration
                $customerPersister = new CustomerPersister(
                    $this->context,
                    $this->get('hashing'),
                    $this->getTranslator(),
                    false
                );

                $customer = new Customer();
                $customer->email = $apiResponse->email;
                $customer->firstname = $apiResponse->firstname;
                $customer->lastname = $apiResponse->lastname;
                if (isset($apiResponse->birthdate)) {
                    $customer->birthday = $apiResponse->birthdate;
                }

                $clearTextPassword = base64_encode(openssl_random_pseudo_bytes(66));

                $customerPersister->save(
                    $customer,
                    $clearTextPassword
                );
            }

            $customer = new Customer();
            $customer->getByEmail($apiResponse->email);

            if (count($jamUserRepository->findBy(['jam_id' => $apiResponse->jam_id, 'user_id' => $customer->id])) === 0) {
                $jamUser = new JamUser();
                $jamUser->setJamId($apiResponse->jam_id)
                    ->setUserId($customer->id);
                $entityManager->persist($jamUser);
            }

            // Do login
            return $this->handlePsLogin($customer);
        } else {
            // Not the first JAM login
            if (count($jamUserRepository->findBy(['jam_id' => $apiResponse->jam_id])) > 0) {
                // User exists in our link table
                /** @var JamUser $jamUser */
                $jamUser = $jamUserRepository->findBy(['jam_id' => $apiResponse->jam_id]);
                $customer = new Customer($jamUser->getUserId());

                // Do login
                return $this->handlePsLogin($customer);
            } else {
                // Error: User does not exists, tell them that they need to remove the service from their app
                $this->error = $this->l('You do not exists in our customer database. Please remove the Service from your JustAuthme app and try again.');

                return false;
            }
        }
    }

    private function getJamUser(string $jam_id): ?string
    {
        $sql = new DbQuery();
        $sql->select('`user_id`');
        $sql->from(JustAuthMe::USER_TABLE);
        $sql->where('`jam_id` = \'' . pSQL($jam_id) . '\'');

        return Db::getInstance()->getValue($sql);
    }

    private function handlePsLogin(Customer $customer): bool
    {
        Hook::exec('actionAuthenticationBefore');

        if (isset($customer->active) && !$customer->active) {
            $this->error = $this->translator->trans('Your account isn\'t available at this time, please contact us', [], 'Shop.Notifications.Error');

            return false;
        }

        $this->context->updateCustomer($customer);

        Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

        // Login information have changed, so we check if the cart rules still apply
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        return true;
    }
}
