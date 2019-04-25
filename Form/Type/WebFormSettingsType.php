<?php

namespace Monolith\Module\WebForm\Form\Type;

use Monolith\Module\WebForm\Entity\WebForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebFormSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, ['attr'  => ['autofocus' => 'autofocus']])
            ->add('name')
            ->add('is_ajax', null, ['required' => false])
            ->add('is_use_captcha', null, ['required' => false])
            ->add('send_button_title')
            ->add('send_notice_emails')
            ->add('from_email')
            ->add('from_name')
            ->add('final_text')
            ->add('smtp_server')
            ->add('smtp_user')
            ->add('smtp_password')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WebForm::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'monolith_module_webform_settings';
    }
}
