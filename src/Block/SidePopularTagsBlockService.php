<?php

namespace App\Block;

use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\ClassificationBundle\Model\TagManagerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\ClassificationBundle\Model\ContextInterface;
use Sonata\ClassificationBundle\Model\ContextManagerInterface;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SidePopularTagsBlockService extends BaseBlockService
{

    /**
     * @var TagManagerInterface
     */
    protected $manager;

    /**
     * @var ContextManagerInterface
     */
    protected $contextManager;

    /**
     * @param string           $name
     * @param EngineInterface  $templating
     * @param ManagerInterface $postManager
     * @param Pool             $adminPool
     */
    public function __construct($name, EngineInterface $templating, ManagerInterface $tagManager, ContextManagerInterface $contextManager)
    {
        $this->manager   = $tagManager;
        $this->contextManager = $contextManager;

        parent::__construct($name, $templating);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $criteria = array(
            'enabled' => '1',
        	'context' => $blockContext->getSetting('context'),
        	'id' => 3,
        );
        $sort = array(
        		// sort by tag usage count if possible
//        	'post.id' => 'desc',
        );

        $parameters = array(
            'context'    => $blockContext,
            'settings'   => $blockContext->getSettings(),
            'block'      => $blockContext->getBlock(),
            'pager'      => $this->manager->getPager($criteria, 1, $blockContext->getSetting('number'), $sort),
        );

        return $this->renderResponse($blockContext->getTemplate(), $parameters, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $contextChoices = $this->getContextChoices();

        $formMapper->add('settings', ImmutableArrayType::class, array(
            'keys' => array(
                    array('title', TextType::class, array(
                    'required' => true,
                    'label'    => 'form.label_title',
                )),
                array('number', 'integer', array(
                    'required' => true,
                    'label'    => 'form.label_number',
                )),
                    array('context', ChoiceType::class, array(
                    'required' => false,
                    'choices'  => $contextChoices,
                    'label'    => 'form.label_context',
                )),
            ),
            'translation_domain' => 'RylReignThemeBundle',
        ));
    }

    /**
     *
     * @return array
     */
    protected function getContextChoices()
    {
    	$contextChoices = array();

    	$contexts = $this->contextManager->findAll();

    	foreach ($contexts as $code => $context) {
    		$contextChoices[$context->getId()] = $context->getName();
    	}

    	return $contextChoices;
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'title'      => 'Popular tags',
        	'number'     => 5,
        	'context'	 => 'news',
            'template'   => '@App/Block/tags_popular_side.html.twig',
        ));
    }

}
