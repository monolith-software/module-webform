<?php

declare(strict_types=1);

namespace Monolith\Module\WebForm\Entity;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;

/**
 * @ORM\Entity(repositoryClass="MessageRepository")
 * @ORM\Table(name="webforms_messages")
 */
class Message
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;
    use ColumnTrait\IpAddress;
    use ColumnTrait\FosUser;

    const STATUS_NEW         = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_FINISHED    = 2;
    const STATUS_REJECTED    = 3;
    const STATUS_SPAM        = 5;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    protected $data;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"default":0})
     */
    protected $status;

    /**
     * @var WebForm
     *
     * @ORM\ManyToOne(targetEntity="WebForm", inversedBy="messages")
     */
    protected $web_form;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->status     = self::STATUS_NEW;
    }

    /**
     * @return string
     */
    public function getBriefly(): string
    {
        $str = '';

        foreach ($this->getData() as $data) {
            $str .= $data.', ';
        }

        $a = strip_tags($str);

        $dotted = (mb_strlen($a, 'utf-8') > 80) ? '...' : '';

        return mb_substr($a, 0, 80, 'utf-8').$dotted;
    }

    /**
     * @return array
     */
    public static function getFormChoicesStatuses(): array
    {
        return [
            'Новый'    => self::STATUS_NEW,
            'В работе' => self::STATUS_IN_PROGRESS,
            'Выполнен' => self::STATUS_FINISHED,
            'Оклонён'  => self::STATUS_REJECTED,
            'Спам'     => self::STATUS_SPAM,
        ];
    }

    /**
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return $this
     */
    public function setComment($comment): Message
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getDataValue($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data): Message
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status): Message
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return WebForm
     */
    public function getWebForm(): WebForm
    {
        return $this->web_form;
    }

    /**
     * @param WebForm $web_form
     *
     * @return $this
     */
    public function setWebForm(WebForm $web_form): Message
    {
        $this->web_form = $web_form;

        return $this;
    }
}
