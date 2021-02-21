<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\StockTradeStatement;

class TradeStatementType extends StatementType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//      $builder
  //      ->add('tradeUnit');
      parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
      $resolver->setDefaults([
          'data_class' => StockTradeStatement::class,
      ]);
    }
}
