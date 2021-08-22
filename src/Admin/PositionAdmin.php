<?php

declare(strict_types=1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Form\Type\ModelType;

final class PositionAdmin extends AbstractAdmin
{

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('id')
            ->add('portfolio')
            ->add('contract')
            ->add('quantity')
            ->add('cost')
            ;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('id')
            ->add('portfolio')
            ->add('contract')
            ->add('quantity')
            ->add('cost')
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
            ->add('portfolio', ModelType::class, [ 'required' => true ])
            ->add('openDate')
            ->add('contract', ModelType::class, [ 'required' => true ])
            ->add('quantity')
            ->add('cost')
            ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('id')
            ->add('portfolio', ModelType::class, [ 'required' => true ])
            ->add('openDate')
            ->add('contract', ModelType::class, [ 'required' => true ])
            ->add('quantity')
            ->add('cost')
            ;
    }
}
