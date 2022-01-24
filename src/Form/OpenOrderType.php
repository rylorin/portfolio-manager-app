<?php

namespace App\Form;

use App\Entity\OpenOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpenOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('PermId')
            ->add('ClientId')
            ->add('OrderId')
            ->add('ActionType')
            ->add('TotalQty')
            ->add('CashQty')
            ->add('LmtPrice')
            ->add('AuxPrice')
            ->add('Status')
            ->add('RemainingQty')
            ->add('Account')
            ->add('contract')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OpenOrder::class,
        ]);
    }
}
