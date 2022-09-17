<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TradeUnit;
use App\Entity\Contract;
use App\Entity\Stock;
use App\Entity\Future;
use App\Form\Type\UnderlyingType;

class TradeUnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('symbol', UnderlyingType::class, [ 'required' => true ])
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
          ->add('risk')
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
