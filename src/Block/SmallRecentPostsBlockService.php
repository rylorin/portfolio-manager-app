<?php

namespace App\Block;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\NewsBundle\Block\RecentPostsBlockService;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\CoreBundle\Model\ManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SmallRecentPostsBlockService
 *
 * Renders a block
 *
 */
class SmallRecentPostsBlockService extends RecentPostsBlockService
{
	/**
	 * @var BaseCollectionAdmin
	 */
	protected $collectionAdmin;

	/**
	 * @var ManagerInterface
	 */
	protected $collectionManager;

    /**
     * @param string             $name
     * @param EngineInterface    $templating
     * @param ContainerInterface $container
     * @param ManagerInterface   $collectionManager
     */
    public function __construct($name, EngineInterface $templating, ManagerInterface $postManager, ContainerInterface $container, ManagerInterface $collectionManager)
    {
        parent::__construct($name, $templating, $postManager);

        $this->collectionManager = $collectionManager;
        $this->container    = $container;
    }

	/**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
        	'title'	   	 => 'Recent Posts',
            'number'     => 3,
            'mode'       => 'public',
        	'template' 	 => '@App/Block/posts-sm.html.twig',
            'collectionId'  => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
    	/* RYL: a quoi ca sert, ca fonctionne sans */
        if (!$block->getSetting('collectionId') instanceof CollectionInterface) {
            $this->load($block);
        }

        $formMapper->add('settings', ImmutableArrayType::class, array(
    			'keys' => array(
    					array('number', 'integer', array(
    							'required' => true,
    							'label'    => 'form.label_number',
    					)),
    			        array('title', TextType::class, array(
    							'required' => false,
    							'label'    => 'form.label_title',
    					)),
    			        array('mode', ChoiceType::class, array(
    							'choices' => array(
    									'public' => 'form.label_mode_public',
    									'admin'  => 'form.label_mode_admin',
    							),
    							'label' => 'form.label_mode',
    					)),
                		array($this->getCollectionBuilder($formMapper), null, array()),
    			),
    			'translation_domain' => 'SonataNewsBundle',
    	));
    }

    /**
     * {@inheritdoc}
     */
    public function load(BlockInterface $block)
    {
        $collection = $block->getSetting('collectionId', null);

        if (is_int($collection)) {
            $collection = $this->collectionManager->findOneBy(array('id' => $collection));
        }

        $block->setSetting('collectionId', $collection);
    }

    /**
     * @param FormMapper $formMapper
     *
     * @return FormBuilder
     */
    protected function getCollectionBuilder(FormMapper $formMapper)
    {
        // simulate an association ...
        $fieldDescription = $this->getCollectionAdmin()->getModelManager()->getNewFieldDescriptionInstance($this->collectionAdmin->getClass(), 'collection', array(
            'translation_domain' => 'SonataCollectionBundle',
        ));
        $fieldDescription->setAssociationAdmin($this->getCollectionAdmin());
        $fieldDescription->setAdmin($formMapper->getAdmin());
        $fieldDescription->setOption('edit', 'list');
        $fieldDescription->setAssociationMapping(array(
            'fieldName' => 'collection',
            'type'      => ClassMetadataInfo::MANY_TO_ONE,
        ));

        return $formMapper->create('collectionId', 'sonata_type_model_list', array(
            'sonata_field_description' => $fieldDescription,
            'class'                    => $this->getCollectionAdmin()->getClass(),
            'model_manager'            => $this->getCollectionAdmin()->getModelManager(),
            'label'                    => 'form.label_collection',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(BlockInterface $block)
    {
        $block->setSetting('collectionId', is_object($block->getSetting('collectionId')) ? $block->getSetting('collectionId')->getId() : null);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(BlockInterface $block)
    {
        $block->setSetting('collectionId', is_object($block->getSetting('collectionId')) ? $block->getSetting('collectionId')->getId() : null);
    }

    /**
     * @return BaseMediaAdmin
     */
    public function getCollectionAdmin()
    {
    	if (!$this->collectionAdmin) {
    		$this->collectionAdmin = $this->container->get('sonata.classification.admin.collection');
    	}

    	return $this->collectionAdmin;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $criteria = array(
            'mode' => $blockContext->getSetting('mode'),
            'collection' => $blockContext->getBlock()->getSetting('collectionId'),
        );

        $parameters = array(
            'context'    => $blockContext,
            'block'      => $blockContext->getBlock(),
        	'settings'   => $blockContext->getSettings(),
            'pager'      => $this->manager->getPager($criteria, 1, $blockContext->getSetting('number')),
            'admin_pool' => $this->adminPool,
        );

        if ($blockContext->getSetting('mode') === 'admin') {
            return $this->renderPrivateResponse($blockContext->getTemplate(), $parameters, $response);
        }

        return $this->renderResponse($blockContext->getTemplate(), $parameters, $response);
    }

}
