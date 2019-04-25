<?php

namespace Monolith\Module\WebForm\Form\Type;

use Monolith\Module\WebForm\Entity\WebForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, ['attr'  => ['autofocus' => 'autofocus']])
            ->add('name')
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
        return 'monolith_module_webform';
    }
}
