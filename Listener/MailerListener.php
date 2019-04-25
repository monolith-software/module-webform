<?php

declare(strict_types=1);

namespace Monolith\Module\WebForm\Listener;

use Monolith\Module\WebForm\Entity\Message;
use Monolith\Module\WebForm\Entity\WebForm;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MailerListener implements EventSubscriberInterface
{
    use ContainerAwareTrait;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        $listeners = [KernelEvents::TERMINATE => 'onTerminate'];

        if (class_exists('Symfony\Component\Console\ConsoleEvents')) {
            $listeners[ConsoleEvents::TERMINATE] = 'onTerminate';
        }

        return $listeners;
    }

    public function onTerminate()
    {
        $messages = $this->container->get('web_form.mail.collector')->getMessages();

        foreach ($messages as $data) {
            /** @var WebForm $webForm */
            $webForm = $data['webForm'];
            /** @var \Swift_Message $message */
            $message = $data['message'];
            $transport = (new \Swift_SmtpTransport($webForm->getSmtpServer(), 465, 'ssl'))
                ->setUsername($webForm->getSmtpUser())
                ->setPassword($webForm->getSmtpPassword())
            ;

            $mailer = new \Swift_Mailer($transport);
            $result = $mailer->send($message);
        }
    }
}
