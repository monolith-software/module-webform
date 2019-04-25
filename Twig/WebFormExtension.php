<?php

declare(strict_types=1);

namespace Monolith\Module\WebForm\Twig;

use Monolith\Module\WebForm\Entity\Message;
use Monolith\Module\WebForm\Entity\WebForm;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WebFormExtension extends AbstractExtension
{
    use ContainerAwareTrait;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('module_webform_count_new',  [$this, 'getNewMessagesCount']),
            new TwigFunction('module_webform_count_inprogress',  [$this, 'getInProgressCount']),
        ];
    }

    /**
     * @param WebForm $webForm
     *
     * @return int
     */
    public function getNewMessagesCount(WebForm $webForm)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        return $em->getRepository('WebFormModuleBundle:Message')->getCountByStatus($webForm, Message::STATUS_NEW);
    }

    /**
     * @param WebForm $webForm
     *
     * @return int
     */
    public function getInProgressCount(WebForm $webForm)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        return $em->getRepository('WebFormModuleBundle:Message')->getCountByStatus($webForm, Message::STATUS_IN_PROGRESS);
    }
}
