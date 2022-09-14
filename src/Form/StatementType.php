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
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityRepository;

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
            ->add('stock', EntityType::class, [
                // looks for choices from this entity
                'class' => Contract::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('q')
                    ->Join('App\Entity\Stock', 's', 'WITH', 'q.id = s.id')
                    // ->leftJoin('App\Entity\IndexContract', 'i', 'WITH', 'i.id = q.id')
                        // ->add('from', 'App\Entity\Contract q, App\Entity\Stock s, App\Entity\IndexContract i')
                        // ->where("s.id = q.id OR i.id = q.id")
                        ->orderBy('q.symbol', 'ASC');
                },
            
                // uses the User.username property as the visible option string
                //'choice_label' => 'username',

                // used to render a select box, check boxes or radios
                // 'multiple' => true,
                // 'expanded' => true,
                'required' => false
                ])
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
