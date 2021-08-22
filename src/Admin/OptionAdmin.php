<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sonata\AdminBundle\Form\Type\ModelType;

final class OptionAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('symbol')
            ->add('callOrPut')
            ->add('multiplier')
            ;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->add('symbol')
            ->add('lastTradeDate')
            ->add('strike')
            ->add('callOrPut')
            ->add('multiplier')
            ->add('price')
            ->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('id')
            ->add('conId')
            ->add('symbol')
            ->add('stock', ModelType::class, [ 'required' => true ])
            ->add('lastTradeDate')
            ->add('strike')
            ->add('callOrPut', ChoiceType::class, [ 'choices' => [ 'Call' => 'C', 'Put' => 'P' ]])
            ->add('multiplier')
            ->add('price')
            ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('conId')
            ->add('symbol')
            ->add('lastTradeDate')
            ->add('strike')
            ->add('callOrPut', ChoiceType::class, [ 'choices' => [ 'Call' => 'C', 'Put' => 'P' ]])
            ->add('multiplier')
            ->add('price')
            ;
    }
}
