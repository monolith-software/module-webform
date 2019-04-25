<?php

declare(strict_types=1);

namespace Monolith\Module\WebForm\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="webforms")
 */
class WebForm
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\NameUnique;
    use ColumnTrait\TitleNotBlank;
    use ColumnTrait\FosUser;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $is_ajax;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $is_use_captcha;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $send_button_title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $send_notice_emails;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Email()
     */
    protected $from_email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $from_name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $final_text;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_message_date;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=64)
     */
    protected $smtp_server;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=64)
     */
    protected $smtp_user;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=64)
     */
    protected $smtp_password;

    /**
     * @var WebFormField[]
     *
     * @ORM\OneToMany(targetEntity="WebFormField", mappedBy="web_form")
     * @ORM\OrderBy({"position" = "ASC"})
     */
    protected $fields;

    /**
     * @var Message[]
     *
     * @ORM\OneToMany(targetEntity="Message", mappedBy="web_form")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $messages;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->created_at       = new \DateTime();
        $this->fields           = new ArrayCollection();
        $this->is_use_captcha   = false;
        $this->is_ajax          = false;
        $this->messages         = new ArrayCollection();
    }

    /**
     * @see getName
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getTitle();
    }

    /**
     * @return WebFormField[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param WebFormField[] $fields
     *
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     *
     * @return $this
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsAjax(): bool
    {
        return $this->is_ajax;
    }

    /**
     * @param bool $is_ajax
     *
     * @return $this
     */
    public function setIsAjax($is_ajax): WebForm
    {
        $this->is_ajax = $is_ajax;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsUseCaptcha(): bool
    {
        return $this->is_use_captcha;
    }

    /**
     * @param bool $is_use_captcha
     *
     * @return $this
     */
    public function setIsUseCaptcha($is_use_captcha): WebForm
    {
        $this->is_use_captcha = $is_use_captcha;

        return $this;
    }

    /**
     * @return string
     */
    public function getSendButtonTitle(): ?string
    {
        return $this->send_button_title;
    }

    /**
     * @param string $send_button_title
     *
     * @return $this
     */
    public function setSendButtonTitle($send_button_title): WebForm
    {
        $this->send_button_title = $send_button_title;

        return $this;
    }

    /**
     * @return string
     */
    public function getSendNoticeEmails(): ?string
    {
        return $this->send_notice_emails;
    }

    /**
     * @param string $send_notice_emails
     *
     * @return $this
     */
    public function setSendNoticeEmails($send_notice_emails): WebForm
    {
        $this->send_notice_emails = $send_notice_emails;

        return $this;
    }

    /**
     * @return string
     */
    public function getFinalText(): ?string
    {
        return $this->final_text;
    }

    /**
     * @param string $final_text
     *
     * @return $this
     */
    public function setFinalText($final_text): WebForm
    {
        $this->final_text = $final_text;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromEmail(): ?string
    {
        return $this->from_email;
    }

    /**
     * @param string $from_email
     *
     * @return $this
     */
    public function setFromEmail($from_email): WebForm
    {
        $this->from_email = $from_email;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFromName(): ?string
    {
        return $this->from_name;
    }

    /**
     * @param null|string $from_name
     *
     * @return $this
     */
    public function setFromName($from_name): WebForm
    {
        $this->from_name = $from_name;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastMessageDate(): ?\DateTime
    {
        return $this->last_message_date;
    }

    /**
     * @param \DateTime $last_message_date
     *
     * @return $this
     */
    public function setLastMessageDate(\DateTime $last_message_date): WebForm
    {
        $this->last_message_date = $last_message_date;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSmtpServer(): ?string
    {
        return $this->smtp_server;
    }

    /**
     * @param null|string $smtp_server
     *
     * @return $this
     */
    public function setSmtpServer($smtp_server): WebForm
    {
        $this->smtp_server = $smtp_server;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSmtpUser(): ?string
    {
        return $this->smtp_user;
    }

    /**
     * @param null|string $smtp_user
     *
     * @return $this
     */
    public function setSmtpUser($smtp_user): WebForm
    {
        $this->smtp_user = $smtp_user;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getSmtpPassword(): ?string
    {
        return $this->smtp_password;
    }

    /**
     * @param null|string $smtp_password
     *
     * @return $this
     */
    public function setSmtpPassword($smtp_password): WebForm
    {
        $this->smtp_password = $smtp_password;

        return $this;
    }
}
