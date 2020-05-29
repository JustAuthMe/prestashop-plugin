<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class JustAuthMe extends Module
{
    const DB_PREFIX = 'jam_';
    const USER_TABLE = self::DB_PREFIX . 'user';
    const CONFIG_APP_ID = 'JUSTAUTHME_APPID';
    const CONFIG_API_SECRET = 'JUSTAUTHME_SECRET';
    const CONFIG_CALLBACK_URL = 'JUSTAUTHME_CALLBACK';

    public function __construct()
    {
        $this->name = 'JustAuthMe';
        $this->version = '1.0.0';
        $this->tab = 'front_office_features';
        $this->author = 'JustAuthMe';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('JustAuthMe');
        $this->description = $this->l('Passwordless SSO');
    }

    public function install(): bool
    {
        $return = parent::install();

        if ($return) {
            $return &= $this->registerHook('displayCustomerAccountFormTop');
            $return &= $this->registerHook('displayCustomerLoginFormAfter');

            if ($return) {
                $return &= Db::getInstance()->execute('
                    CREATE TABLE IF NOT EXISTS `' . self::USER_TABLE . '` (
                      `id` int UNSIGNED NOT NULL,
                      `user_id` int UNSIGNED NOT NULL,
                      `jam_id` varchar(255) NOT NULL,
                      `link_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;
                ');

                    $return &= Db::getInstance()->execute('
                    ALTER TABLE `' . self::USER_TABLE . '`
                      ADD PRIMARY KEY (`id`),
                      ADD KEY `user_id` (`user_id`);
                ');

                    $return &= Db::getInstance()->execute('
                    ALTER TABLE `' . self::USER_TABLE . '`
                      MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
                ');

                    $return &= Db::getInstance()->execute('
                    ALTER TABLE `' . self::USER_TABLE . '`
                      ADD CONSTRAINT `jam_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `ps_customer` (`id_customer`) ON DELETE CASCADE ON UPDATE CASCADE;
                    COMMIT;
                ');
            }
        }

        return $return;
    }

    public function uninstall(): bool
    {
        $return = parent::uninstall();
        
        $return &= Db::getInstance()->execute('
            DROP TABLE IF EXISTS `' . self::USER_TABLE . '`
        ');

        return $return;
    }

    public function getContent(): string
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $app_id = strval(Tools::getValue(self::CONFIG_APP_ID));
            $secret = strval(Tools::getValue(self::CONFIG_API_SECRET));
            //$callback = strval(Tools::getValue(self::CONFIG_CALLBACK_URL));

            if (empty($app_id) || empty($secret) /*|| empty($callback)*/) {
                $output .= $this->displayError('App ID, API secret and callback URL are required.');
            } else {
                Configuration::updateValue(self::CONFIG_APP_ID, $app_id);
                Configuration::updateValue(self::CONFIG_API_SECRET, $secret);
                //Configuration::updateValue(self::CONFIG_CALLBACK_URL, $callback);
                $output .= $this->displayConfirmation('JustAuthMe successfully set up.');
            }
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('JustAuthMe settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('App ID'),
                    'name' => self::CONFIG_APP_ID,
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('API secret'),
                    'name' => self::CONFIG_API_SECRET,
                    'size' => 20,
                    'required' => true
                ]/*,
                [
                    'type' => 'text',
                    'label' => $this->l('Callback URL'),
                    'name' => self::CONFIG_CALLBACK_URL,
                    'size' => 20,
                    'required' => true
                ]*/
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // true -> Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value[self::CONFIG_APP_ID] = Tools::getValue(self::CONFIG_APP_ID, Configuration::get(self::CONFIG_APP_ID));
        $helper->fields_value[self::CONFIG_API_SECRET] = Tools::getValue(self::CONFIG_API_SECRET, Configuration::get(self::CONFIG_API_SECRET));
        //$helper->fields_value[self::CONFIG_CALLBACK_URL] = Tools::getValue(self::CONFIG_CALLBACK_URL, Configuration::get(self::CONFIG_CALLBACK_URL));

        return $helper->generateForm($fieldsForm);
    }

    private function displayButton(): string
    {
        $this->context->smarty->assign([
            'app_id' => Configuration::get(self::CONFIG_APP_ID),
            'callback_url' => Configuration::get(self::CONFIG_CALLBACK_URL)
        ]);

        return $this->display(__FILE__, 'button.tpl');
    }

    public function hookDisplayCustomerAccountFormTop(array $params): string
    {
        return $this->displayButton();
    }

    public function hookDisplayCustomerLoginFormAfter(array $params): string
    {
        return $this->displayButton();
    }
}
