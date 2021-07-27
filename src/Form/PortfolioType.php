<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Portfolio;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class PortfolioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['required' => true])
            ->add('account', null, ['required' => true])
            ->add('baseCurrency', null, ['required' => true])
            ->add('benchmark', null, ['required' => true])
            ->add('FindSymbolsSleep', IntegerType::class, ['required' => true])
            ->add('AdjustCashSleep', IntegerType::class, ['required' => true])
            ->add('rollOptionsSleep', IntegerType::class, ['required' => true])
            ->add('sellNakedPutSleep', IntegerType::class, ['required' => true])
            ->add('PutRatio', PercentType::class, ['required' => true ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Portfolio::class,
        ]);
    }
}
