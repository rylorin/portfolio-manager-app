<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\Statement;
use App\Entity\StockTradeStatement;

class TradeStatementType extends StatementType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      parent::buildForm($builder, $options);
      $builder
      ->add('status', ChoiceType::class, [ 'choices' => [
        'Opening' => Statement::OPEN_STATUS,
        'Closing' => Statement::CLOSE_STATUS,
        'Expired' => Statement::EXPIRED_STATUS,
        'Assigned' => Statement::ASSIGNED_STATUS,
        'Exercised' => Statement::EXERCISED_STATUS,
        ]])
        ->add('quantity')
        ->add('price')
        ->add('proceeds')
        ->add('fees')
        ->add('realizedPNL')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
      $resolver->setDefaults([
          'data_class' => StockTradeStatement::class,
      ]);
    }
}
