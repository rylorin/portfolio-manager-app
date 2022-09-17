<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Statement;
use App\Entity\Contract;
use App\Entity\IndexContract;
use App\Entity\Stock;
use App\Entity\Future;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityRepository;
use App\Form\Type\UnderlyingType;

class StatementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('transactionID')
            ->add('tradeUnit')
            ->add('date', DateTimeType::class, [
                    'html5' => true,
                    'date_widget' => 'single_text',
                    'time_widget' => 'single_text', 'with_seconds' => true
                    ])
            ->add('amount', NumberType::class, [
                    'scale' => 9
                    ])
            ->add('stock', UnderlyingType::class, [ 'required' => false ])
            ->add('description')
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Statement::class,
        ]);
    }
}
