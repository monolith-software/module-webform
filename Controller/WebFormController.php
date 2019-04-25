<?php

namespace Monolith\Module\WebForm\Controller;

use DS\Component\ReCaptchaValidator\Form\ReCaptchaType;
use Genemu\Bundle\FormBundle\Form\Core\Type\CaptchaType;
use KleeGroup\GoogleReCaptchaBundle\Validator\Constraints\ReCaptcha;
use libphonenumber\PhoneNumber;
use Monolith\Bundle\CMSBundle\Annotation\NodePropertiesForm;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Smart\CoreBundle\Controller\Controller;
use Smart\CoreBundle\Form\TypeResolverTtait;
use Monolith\Bundle\CMSBundle\Module\NodeTrait;
use Monolith\Module\WebForm\Entity\Message;
use Monolith\Module\WebForm\Entity\WebForm;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebFormController extends Controller
{
    use NodeTrait;
    use TypeResolverTtait;

    /** @var int|null */
    protected $webform_id;

    /**
     * @param null $options
     *
     * @return Response
     *
     * @NodePropertiesForm("NodePropertiesFormType")
     */
    public function indexAction(Node $node, $webform_id, $options = null, Request $request)
    {
        if ($request->isMethod('POST')) {
            return $this->postAction($node->getId(), $webform_id, $request);
        }

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $webForm = $em->find(WebForm::class, $webform_id);

        $feedback_data = $this->getFlash('feedback_data');
        $web_form_errors = $this->getFlash('web_form_errors');

        if (!empty($feedback_data)) {
            $options['defaults'] = $feedback_data[0]['web_form_'.$webForm->getName()];
        }

        if (isset($options['defaults']) and is_array($options['defaults'])) {
            $form = $this->getForm($node->getId(), $webForm, $options['defaults']);
        } else {
            $form = $this->getForm($node->getId(), $webForm);
        }

        $node->addFrontControl('web_form-'.md5(microtime()))
            ->setTitle('Управление веб-формой')
            ->setUri($this->generateUrl('web_form.admin_new_messages', [
                'name' => $webForm->getName(),
            ]));

        return $this->render('@WebFormModule/index.html.twig', [
            'form'     => $form->createView(),
            'node_id'  => $node->getId(),
            'errors'   => $web_form_errors,
            'web_form' => $webForm,
            'options'  => $options,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function ajaxAction(Request $request, Node $node): JsonResponse
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $webForm = $em->find(WebForm::class, $node->getParam('webform_id'));

        // @todo продумать момент с _node_id
        $data = $request->request->all();
        foreach ($data as $key => $value) {
            if ($key == '_node_id') {
                unset($data['_node_id']);
                break;
            }

            if (is_array($value) and array_key_exists('_node_id', $value)) {
                unset($data[$key]['_node_id']);
                break;
            }
        }

        foreach ($data as $key => $value) {
            $request->request->set($key, $value);
        }

        $form = $this->getForm($node->getId(), $webForm);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            unset($data['_node_id']);

            $message = new Message();
            $message
                ->setData($data)
                ->setUser($this->getUser())
                ->setWebForm($webForm)
                ->setIpAddress($request->server->get('REMOTE_ADDR'))
            ;
            $this->persist($message, true);

            $webForm->setLastMessageDate(clone $message->getCreatedAt());
            $this->persist($webForm, true);

            $isSpam = false;
            foreach ($webForm->getFields() as $field) {
                if ($field->isAntispam()) {
                    $text_to_check = $data[$field->getName()];

                    if(stripos($text_to_check, 'http:') !== false
                        or stripos($text_to_check, 'https:') !== false
                        or stripos($text_to_check, '@') !== false
                    ) {
                        $isSpam = true;
                        break;
                    }
                }
            }

            if ($isSpam) {
                $message->setStatus(Message::STATUS_SPAM);
                $this->persist($message, true);
            } else {
                try {
                    $this->sendNoticeEmails($webForm, $message);
                } catch (\Swift_RfcComplianceException $e) {
                    return new JsonResponse([
                        'status'  => 'error',
                        'message' => 'Swift_RfcComplianceException',
                        'data'    => [
                            'error' => $e->getMessage()
                        ],
                    ], 500);
                }
            }

            return new JsonResponse([
                'status'  => 'success',
                'message' => $webForm->getFinalText() ? $webForm->getFinalText() : 'Сообщение отправлено.',
                'data'    => [],
            ], 200);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $err) {
            $errors[] = $err->getMessage();
        }

        return new JsonResponse([
            'status'  => 'error',
            'message' => 'При заполнении формы допущены ошибки.',
            'data'    => [
                'request_data' => $request->request->all(),
                'form_errors'  => $errors,
                'form_errors_as_string'  => (string) $form->getErrors(true, false),
            ],
        ], 400);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function ajaxGetFormAction(Request $request, Node $node)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $webForm = $em->find(WebForm::class, $node->getParam('webform_id'));

        // @todo продумать момент с _node_id
        $data = $request->request->all();
        foreach ($data as $key => $value) {
            if ($key == '_node_id') {
                unset($data['_node_id']);
                break;
            }

            if (is_array($value) and array_key_exists('_node_id', $value)) {
                unset($data[$key]['_node_id']);
                break;
            }
        }

        foreach ($data as $key => $value) {
            $request->request->set($key, $value);
        }

        $form = $this->getForm($node->getId(), $webForm);
        $form->handleRequest($request);

        return $this->render('@WebFormModule/index.html.twig', [
            'form'     => $form->createView(),
            'node_id'  => $node->getId(),
            'web_form' => $webForm,
        ]);
    }

    /**
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function postAction($node_id, $webform_id, Request $request)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $webForm = $em->find(WebForm::class, $webform_id);

        $form = $this->getForm($node_id, $webForm);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            unset($data['_node_id']);

            $message = new Message();
            $message
                ->setData($data)
                ->setUser($this->getUser())
                ->setWebForm($webForm)
                ->setIpAddress($request->server->get('REMOTE_ADDR'))
            ;
            $this->persist($message, true);

            $webForm->setLastMessageDate(clone $message->getCreatedAt());
            $this->persist($webForm, true);

            $isSpam = false;
            foreach ($webForm->getFields() as $field) {
                if ($field->isAntispam()) {
                    $text_to_check = $data[$field->getName()];

                    if(stripos($text_to_check, 'http:') !== false
                        or stripos($text_to_check, 'https:') !== false
                        or stripos($text_to_check, '@') !== false
                    ) {
                        $isSpam = true;
                        break;
                    }
                }
            }

            if ($isSpam) {
                $message->setStatus(Message::STATUS_SPAM);
                $this->persist($message, true);
            } else {
                $this->sendNoticeEmails($webForm, $message);
            }

            $this->addFlash('success', $webForm->getFinalText() ? $webForm->getFinalText() : 'Сообщение отправлено.');
        } else {
            $errors = [];
            foreach ($form->getErrors(true) as $err) {
                $errors[] = $err->getMessage();
            }

            $this->addFlash('error', 'При заполнении формы допущены ошибки.');
            $this->addFlash('feedback_data', $request->request->all());
            $this->addFlash('web_form_errors', $errors);
        }

        return $this->redirect($request->getRequestUri());
    }

    /**
     * @param WebForm $webForm
     * @param Message $message
     */
    protected function sendNoticeEmails(WebForm $webForm, Message $message)
    {
        if ($webForm->getSendNoticeEmails()) {
            $addresses = [];

            foreach (explode(',', $webForm->getSendNoticeEmails()) as $email) {
                $addresses[] = trim($email);
            }

            if (empty($webForm->getFromName())) {
                $from = $webForm->getFromEmail();
            } else {
                //$from = iconv('utf-8', 'utf-8', $webForm->getFromName()).' <'.$webForm->getFromEmail().'>';
                $from = $webForm->getFromName().' <'.$webForm->getFromEmail().'>';
            }

            $subject = 'Сообщение с веб-формы «'.$webForm->getTitle().'» ('.$this->container->getParameter('base_url').')';

            $headers = 'Content-type: text/plain; charset=utf-8' . "\r\n";

            if (!empty($webForm->getFromEmail())) {
                $headers .= 'From: '.$from;
            }

            $body = $this->renderView('@WebFormModule/Email/notice.email.twig', ['web_form' => $webForm, 'message' => $message]);





            if (empty($webForm->getFromName())) {
                $from = $webForm->getFromEmail();
            } else {
                $from = [$webForm->getFromEmail() => $webForm->getFromName()];
            }

            $message = (new \Swift_Message($subject))
                ->setFrom($from)
                ->setTo($addresses)
                ->setBody($body)
            ;

            $this->get('mailer')->send($message);

            /*
            if (empty($webForm->getSmtpServer())
                or empty($webForm->getSmtpUser())
                or empty($webForm->getSmtpPassword())
            ) {
                mail($webForm->getSendNoticeEmails(), $subject, $body, $headers);
            } else {
                $message = (new \Swift_Message($subject))
                    ->setFrom($from)
                    ->setTo($addresses)
                    ->setBody($body)
                ;

                $this->get('web_form.mail.collector')->addMessage($webForm, $message);
            }
            */
        }
    }

    /**
     * @param WebForm $webForm
     * @param array   $defaults
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm(int $node_id, WebForm $webForm, array $defaults = [])
    {
        $fb = $this->get('form.factory')->createNamedBuilder('web_form_'.$webForm->getName());
        $fb
            ->add('_node_id', HiddenType::class, ['data' => $node_id])
            //->setAttribute('id', 'web_form_'.$webForm->getName())
            ->setErrorBubbling(false)
        ;

        foreach ($webForm->getFields() as $field) {
            if (is_array($field->getParams())) {
                $options = $field->getParams();
            } else {
                $options = [];
            }

            $options['required'] = $field->getIsRequired();
            $options['label'] = $field->getTitle();

            // @todo подставлять дефолтное значение для типа \libphonenumber\PhoneNumber
            if (isset($defaults[$field->getName()]) and $field->getType() !== 'tel') {
                $options['data'] = $defaults[$field->getName()];
            }

            if (isset($options['choices'])) {
                $options['choices'] = array_flip($options['choices']);
                $options['choice_translation_domain'] = false;
            }

            $type = $this->resolveTypeName($field->getType());

            $options['translation_domain'] = false;

            $fb->add($field->getName(), $type, $options);
        }

        if ($webForm->isIsUseCaptcha()) {
            $fb->add('captcha', ReCaptchaType::class, [
                'mapped' => false,
                //'constraints' => [ new ReCaptcha() ],
            ]); // @todo Captcha
        }

        $fb->add('send', SubmitType::class, [
            'attr'  => ['class' => 'btn btn-success'],
            'label' => $webForm->getSendButtonTitle(),
        ]);

        return $fb->getForm();
    }
}
