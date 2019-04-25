<?php

declare(strict_types=1);

namespace Monolith\Module\WebForm\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Smart\CoreBundle\Controller\Controller;
use Monolith\Module\WebForm\Entity\Message;
use Monolith\Module\WebForm\Entity\WebForm;
use Monolith\Module\WebForm\Entity\WebFormField;
use Monolith\Module\WebForm\Form\Type\MessageType;
use Monolith\Module\WebForm\Form\Type\WebFormFieldType;
use Monolith\Module\WebForm\Form\Type\WebFormSettingsType;
use Monolith\Module\WebForm\Form\Type\WebFormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminWebFormController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(WebFormType::class);
        $form->add('create', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($form->get('create')->isClicked()) {
                    $webForm = $form->getData();
                    $webForm->setUser($this->getUser());
                    $this->persist($webForm, true);

                    $this->addFlash('success', 'Веб-форма создана.');
                }

                return $this->redirectToRoute('web_form.admin');
            }
        }

        return $this->render('@WebFormModule/Admin/index.html.twig', [
            'form' => $form->createView(),
            'web_forms' => $em = $this->getDoctrine()->getManager()->getRepository(WebForm::class)->findAll(),
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     *
     * ParamConverter("webForm", options={"mapping": {"name": "name"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldsAction(Request $request, WebForm $webForm)
    {
        $webFormField = new WebFormField();
        $webFormField
            ->setWebForm($webForm)
            ->setUser($this->getUser())
        ;

        $form = $this->createForm(WebFormFieldType::class, $webFormField);
        $form->add('create', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($form->get('create')->isClicked()) {
                    $this->persist($form->getData(), true);
                    $this->addFlash('success', 'Поле создано.');
                }

                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }
        }

        $em = $this->get('doctrine.orm.entity_manager');

        return $this->render('@WebFormModule/Admin/fields.html.twig', [
            'form'            => $form->createView(),
            'nodePath'        => $this->getNodePath($webForm),
            'web_form'        => $webForm,
            'web_form_fields' => $em->getRepository(WebFormField::class)->findBy(['web_form' => $webForm], ['position' => 'ASC']),
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     * @param WebFormField $webFormField
     *
     * @ParamConverter("webForm", options={"mapping": {"name": "name"}})
     * @ParamConverter("webFormField", options={"mapping": {"id": "id"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldEditAction(Request $request, WebForm $webForm, WebFormField $webFormField)
    {
        $form = $this->createForm(WebFormFieldType::class, $webFormField);
        $form
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']])
            ->add('delete', SubmitType::class, ['attr' => ['class' => 'btn-danger', 'onclick' => "return confirm('Вы уверены, что хотите удалить поле?')"]])
            ->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']])
        ;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }

            if ($form->get('delete')->isClicked()) {
                $this->remove($form->getData(), true);
                $this->addFlash('success', 'Поле удалено.');

                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }

            if ($form->isValid() and $form->get('update')->isClicked()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Поле обновлено.');

                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }
        }

        return $this->render('@WebFormModule/Admin/field_edit.html.twig', [
            'form'           => $form->createView(),
            'web_form'       => $webForm,
            'web_form_field' => $webFormField,
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction(Request $request, WebForm $webForm)
    {
        $form = $this->createForm(WebFormSettingsType::class, $webForm);
        $form
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']])
            ->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']])
        ;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
            }

            if ($form->isValid() and $form->get('update')->isClicked()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Настройки обновлены.');

                return $this->redirectToRoute('web_form.admin_settings', ['name' => $webForm->getName()]);
            }
        }

        return $this->render('@WebFormModule/Admin/settings.html.twig', [
            'form'      => $form->createView(),
            'nodePath'  => $this->getNodePath($webForm),
            'web_form'  => $webForm,
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function messagesAction(Request $request, WebForm $webForm, $status)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        switch ($status) {
            case Message::STATUS_IN_PROGRESS:
                $title = 'In progress';
                $routeName = 'web_form.admin_in_progress';
                break;
            case Message::STATUS_FINISHED:
                $title = 'Finished';
                $routeName = 'web_form.admin_finished';
                break;
            case Message::STATUS_REJECTED:
                $title = 'Rejected';
                $routeName = 'web_form.admin_rejected';
                break;
            case Message::STATUS_SPAM:
                $title = 'Spam';
                $routeName = 'web_form.admin_spam';
                break;
            default:
                $title = 'New messages';
                $routeName = 'web_form.admin_new_messages';
        }

        if ($request->isMethod('POST')) {
            $items  = $request->request->get('items');
            $statusNew = $request->request->get('submit');

            $count = 0;
            if (!empty($items) and is_array($items)) {
                foreach ($items as $item_id => $_dummy) {
                    $message = $em->find('WebFormModuleBundle:Message', $item_id);

                    if ($message) {
                        switch ($statusNew) {
                            case 'in_progress':
                                $message->setStatus(Message::STATUS_IN_PROGRESS);
                                break;
                            case 'finished':
                                $message->setStatus(Message::STATUS_FINISHED);
                                break;
                            case 'rejected':
                                $message->setStatus(Message::STATUS_REJECTED);
                                break;
                            case 'spam':
                                $message->setStatus(Message::STATUS_SPAM);
                                break;
                        }

                        $count++;
                        $this->persist($message, true);
                    }
                }
            }

            $this->addFlash('success', "<b>$count</b> сообщений изменено статус на <b>$statusNew</b>.");

            return $this->redirectToRoute($routeName, ['name' => $webForm->getName(), 'page' => $request->query->get('page', 1)]);
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter(
            $em->getRepository('WebFormModuleBundle:Message')->getFindByStatusQuery($webForm, $status)
        ));
        $pagerfanta->setMaxPerPage(20);

        try {
            $pagerfanta->setCurrentPage($request->query->get('page', 1));
        } catch (NotValidCurrentPageException $e) {
            return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
        }

        return $this->render('@WebFormModule/Admin/messages.html.twig', [
            'web_form'   => $webForm,
            'nodePath'   => $this->getNodePath($webForm),
            'pagerfanta' => $pagerfanta,
            'title'      => $title,
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     * @param Message $message
     *
     * @ParamConverter("webForm", options={"mapping": {"name": "name"}})
     * @ParamConverter("Message", options={"mapping": {"id": "id"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editMessageAction(Request $request, WebForm $webForm, Message $message)
    {
        $form = $this->createForm(MessageType::class, $message);
        $form
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']])
            ->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']])
        ;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
            }

            if ($form->isValid() and $form->get('update')->isClicked() and $form->isValid()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Сообщение обновлено.');

                return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
            }
        }

        return $this->render('@WebFormModule/Admin/edit_message.html.twig', [
            'form'      => $form->createView(),
            'web_form'  => $webForm,
        ]);
    }

    /**
     * @param WebForm $webForm
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function manageAction(WebForm $webForm)
    {
        return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
    }

    /**
     * @param \Monolith\Module\WebForm\Entity\WebForm $webForm
     *
     * @return null|string
     *
     * @throws \Exception
     */
    protected function getNodePath(WebForm $webForm)
    {
        foreach ($this->get('cms.node')->findByModule('WebFormModuleBundle') as $node) {
            if ($node->getParam('webform_id') === (int) $webForm->getId()) {
                return $this->get('cms.folder')->getUri($node);
            }
        }

        return null;
    }
}
