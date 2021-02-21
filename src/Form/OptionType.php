<?php

namespace App\Form;

use App\Entity\Option;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('conId')
            ->add('symbol')
            ->add('stock')
            ->add('lastTradeDate')
            ->add('strike')
            ->add('callOrPut', ChoiceType::class, [ 'choices' => [ 'Call' => 'C', 'Put' => 'P' ]])
            ->add('currency')
            ->add('multiplier')
            ->add('price')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Option::class,
        ]);
    }
}
