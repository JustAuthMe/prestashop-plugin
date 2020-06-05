<?php
namespace JustAuthMe\JustAuthMe\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class JamUser
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="jam_id", type="string", length=255)
     */
    private $jamId;

    /**
     * @var string
     *
     * @ORM\Column(name="link_timestamp", type="datetime", nullable=false)
     * @ORM\Version
     */
    private $linkTimestamp;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return JamUser
     */
    public function setId(int $id): JamUser
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return JamUser
     */
    public function setUserId(int $userId): JamUser
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getJamId(): string
    {
        return $this->jamId;
    }

    /**
     * @param string $jamId
     * @return JamUser
     */
    public function setJamId(string $jamId): JamUser
    {
        $this->jamId = $jamId;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinkTimestamp(): string
    {
        return $this->linkTimestamp;
    }

    /**
     * @param string $linkTimestamp
     * @return JamUser
     */
    public function setLinkTimestamp(string $linkTimestamp): JamUser
    {
        $this->linkTimestamp = $linkTimestamp;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'jam_id' => $this->getJamId(),
            'link_timestamp' => $this->getLinkTimestamp()
        ];
    }
}