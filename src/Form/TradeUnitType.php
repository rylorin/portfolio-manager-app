<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use App\Entity\TradeUnit;
use App\Entity\Stock;

class TradeUnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('symbol', EntityType::class, [
            // looks for choices from this entity
            'class' => Stock::class,

            // uses the User.username property as the visible option string
            //'choice_label' => 'username',

            // used to render a select box, check boxes or radios
            // 'multiple' => true,
            // 'expanded' => true,
            ])
          ->add('strategy', ChoiceType::class, [ 'choices' => TradeUnit::stategyMenuMapping ])
          ->add('openingDate', DateTimeType::class, [
                'html5' => true,
                'date_widget' => 'single_text',
                'time_widget' => 'single_text', 'with_seconds' => true
                ])
          ->add('closingDate', DateTimeType::class, [
              'required' => false,
              'html5' => true,
              'date_widget' => 'single_text',
              'time_widget' => 'single_text', 'with_seconds' => true
              ])
          ->add('status', ChoiceType::class, [ 'choices' => [
            'Open' => TradeUnit::OPEN_STATUS, 'Closed' => TradeUnit::CLOSE_STATUS ]])
          ->add('PnL')
          ->add('comment')
          ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TradeUnit::class,
        ]);
    }
}
