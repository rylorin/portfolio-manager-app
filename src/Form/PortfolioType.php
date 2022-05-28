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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PortfolioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['required' => true])
            ->add('account', null, ['required' => true])
            ->add('baseCurrency', null, ['required' => true])
            ->add('benchmark', null, ['required' => true])
            // ->add('FindSymbolsSleep', IntegerType::class, ['required' => true])
            // ->add('crawlerDays', IntegerType::class, ['required' => true])
            ->add('CashStrategy', ChoiceType::class, [
                'choices' => Portfolio::STRATEGIES,
                'choice_label' => null,
                ])
            ->add('AdjustCashSleep', IntegerType::class, ['required' => true])
            ->add('rollOptionsSleep', IntegerType::class, ['required' => true])
            ->add('rollDaysBefore', IntegerType::class, ['required' => true ])
            ->add('sellNakedPutSleep', IntegerType::class, ['required' => true])
            ->add('PutRatio', PercentType::class, ['required' => true ])
            ->add('minPremium', null, ['required' => true ])
            ->add('nakedPutWinRatio', PercentType::class, ['required' => true ])
            ->add('nakedCallWinRatio', PercentType::class, ['required' => true ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Portfolio::class,
        ]);
    }
}
