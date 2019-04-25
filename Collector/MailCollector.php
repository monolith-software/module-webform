<?php

declare(strict_types=1);

namespace Monolith\Module\WebForm\Collector;

class MailCollector
{
    protected $messages = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        
    }

    public function addMessage($webForm, $message): void
    {
        $this->messages[] = [
            'webForm' => $webForm,
            'message' => $message,
        ];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
