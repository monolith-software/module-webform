<?php

namespace Monolith\Module\WebForm;

use Knp\Menu\MenuItem;
use Monolith\Bundle\CMSBundle\Module\ModuleBundle;
use Monolith\Module\WebForm\Entity\Message;
use Monolith\Module\WebForm\Entity\WebForm;

class WebFormModuleBundle extends ModuleBundle
{
    /**
     * Получить обязательные параметры.
     *
     * @return array
     *
     * @deprecated
     */
    public function getRequiredParams(): array
    {
        return ['webform_id'];
    }

    public function getNotifications()
    {
        $data = [];

        $em = $this->container->get('doctrine.orm.entity_manager');

        foreach ($em->getRepository(WebForm::class)->findAll() as $webForm) {
            $count = $em->getRepository('WebFormModuleBundle:Message')
                ->getCountByStatus($webForm, Message::STATUS_NEW)
            ; // @todo fix for sf plugin

            if ($count) {
                // @todo вынести уведомления на уровень движка.
                // new Notification()
                $data[] = ['title' => 'Новые сообщения в веб-форме: ' . $webForm->getTitle(), 'descr' => '', 'count' => $count, 'badge' => 'important', 'icon' => '', 'html' => null, 'url' => $this->container->get('router')
                    ->generate('web_form.admin_new_messages', ['name' => $webForm->getName()]),
                ];
            }
        }

        return $data;
    }

    /**
     * @param MenuItem $menu
     * @param array    $extras
     *
     * @return MenuItem
     */
    public function buildAdminMenu(MenuItem $menu, array $extras = ['beforeCode' => '<i class="fa fa-bullhorn"></i>'])
    {
        if ($this->hasAdmin()) {
            $em = $this->container->get('doctrine.orm.entity_manager');

            $countNew = 0;
            $countInProgress = 0;
            foreach ($em->getRepository(WebForm::class)->findAll() as $webForm) {
                $countNew += $em->getRepository('WebFormModuleBundle:Message')
                    ->getCountByStatus($webForm, Message::STATUS_NEW)
                ;

                $countInProgress += $em->getRepository('WebFormModuleBundle:Message')
                    ->getCountByStatus($webForm, Message::STATUS_IN_PROGRESS)
                ;
            }

            if ($countNew or $countInProgress) {
                $titleNew = $this->container->get('translator')->trans('New messages');
                $titleInProgress = $this->container->get('translator')->trans('In progress');

                $afterCode = '<span class="pull-right-container">';

                if ($countInProgress) {
                    $afterCode .= '<small class="label pull-right bg-green" title="'.$titleInProgress.'">'.$countInProgress.'</small>';
                }

                if ($countNew) {
                    $afterCode .= '<small class="label pull-right bg-red" title="'.$titleNew.'">'.$countNew.'</small>';
                }

                $afterCode .= '</span>';

                $extras['afterCode'] = $afterCode;
            }

            $menu->addChild('WebForms', [
                'uri' => $this->container->get('router')->generate('cms_admin_index').$this->getShortName().'/'
            ])
                ->setExtras($extras)
            ;
        }

        return $menu;
    }
}
