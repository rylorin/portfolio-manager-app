<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\Option;
use App\Form\ContractType;

class OptionType extends ContractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('stock')
            ->add('lastTradeDate')
            ->add('strike')
            ->add('callOrPut', ChoiceType::class, [ 'choices' => [ 'Call' => 'C', 'Put' => 'P' ]])
            ->add('delta')
            ->add('multiplier')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Option::class,
        ]);
    }
}
