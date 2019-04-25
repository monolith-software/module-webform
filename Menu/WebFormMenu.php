<?php

namespace Monolith\Module\WebForm\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Monolith\Module\WebForm\Entity\Message;
use Monolith\Module\WebForm\Entity\WebForm;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class WebFormMenu implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param FactoryInterface $factory
     * @param array $options
     *
     * @return ItemInterface
     */
    public function admin(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('web_form_admin');

        $menu->setChildrenAttribute('class', isset($options['class']) ? $options['class'] : 'nav nav-tabs');

        /** @var WebForm $webForm */
        $webForm = $options['web_form'];

        $em = $this->container->get('doctrine.orm.entity_manager');

        $countNewMessages = $em->getRepository('WebFormModuleBundle:Message')->getCountByStatus($webForm, Message::STATUS_NEW);
        $countInProgress  = $em->getRepository('WebFormModuleBundle:Message')->getCountByStatus($webForm, Message::STATUS_IN_PROGRESS);

        $menu->addChild('New messages', ['route' => 'web_form.admin_new_messages',  'routeParameters' => ['name' => $webForm->getName()]])->setExtras(['countNewMessages' => $countNewMessages]);
        $menu->addChild('In progress',  ['route' => 'web_form.admin_in_progress',   'routeParameters' => ['name' => $webForm->getName()]])->setExtras(['countInProgress'  => $countInProgress]);
        $menu->addChild('Finished',     ['route' => 'web_form.admin_finished',      'routeParameters' => ['name' => $webForm->getName()]]);
        $menu->addChild('Rejected',     ['route' => 'web_form.admin_rejected',      'routeParameters' => ['name' => $webForm->getName()]]);
        $menu->addChild('Spam',         ['route' => 'web_form.admin_spam',          'routeParameters' => ['name' => $webForm->getName()]]);
        $menu->addChild('Fields',       ['route' => 'web_form.admin_fields',        'routeParameters' => ['name' => $webForm->getName()]]);
        $menu->addChild('Settings',     ['route' => 'web_form.admin_settings',      'routeParameters' => ['name' => $webForm->getName()]]);

        return $menu;
    }

    public function test(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('web_form_admin_test');

        return $menu;
    }
}
