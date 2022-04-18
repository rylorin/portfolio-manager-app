<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use App\Entity\TradeParameter;
use App\Entity\Stock;

class TradeParameterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('stock', EntityType::class, [
            // looks for choices from this entity
            'class' => Stock::class,

            // uses the User.username property as the visible option string
            //'choice_label' => 'username',

            // used to render a select box, check boxes or radios
            // 'multiple' => true,
            // 'expanded' => true,
            'required' => true
            ])
            ->add('cspStrategy', ChoiceType::class, [
                'choices' => TradeParameter::CSPSTRATEGIES,
                'choice_label' => null,
                ])
            ->add('navRatio', PercentType::class, [ 'required' => true, 'scale' => 1 ])
            ->add('rollStrategy', ChoiceType::class, [
                'choices' => TradeParameter::ROLLSTRATEGIES,
                'choice_label' => null,
                ])
            ->add('ccStrategy', ChoiceType::class, [
                'choices' => TradeParameter::CCSTRATEGIES,
                'choice_label' => null,
                ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TradeParameter::class,
        ]);
    }
}
