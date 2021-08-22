<?php

declare(strict_types=1);

namespace App\Block;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\MediaBundle\Block\MediaBlockService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class PictureBlockService
 *
 * Renders a picture block
 *
 */
class PictureBlockService extends MediaBlockService
{

    /**
     * Constructor
     *
     * @param string               $name        A block name
     * @param EngineInterface      $templating  Twig engine service
     * @param ContainerInterface
     * @param ManagerInterface
     */
    public function __construct($name, EngineInterface $templating, ContainerInterface $container, ManagerInterface $mediaManager)
    {
        parent::__construct($name, $templating, $container, $mediaManager);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        parent::execute($blockContext);

        return $this->renderPrivateResponse($blockContext->getTemplate(), array(
            'context' => $blockContext,
            'block' => $blockContext->getBlock(),
            'settings' => $blockContext->getSettings(),
            'media' => $blockContext->getSetting('mediaId')
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        parent::buildEditForm($formMapper, $block);

        $formatChoices = $this->getFormatChoices($block->getSetting('mediaId'));
        $formMapper->add('settings', ImmutableArrayType::class, array(
	        'keys' => array(
	            array('title', TextType::class, array('required' => false)),
                array($this->getMediaBuilder($formMapper), null, array()),
	            array('format', ChoiceType::class, array('required' => count($formatChoices) > 0, 'choices' => $formatChoices)),
	        )
	    ));
    }


    /**
     * {@inheritdoc}
     */
    public function buildCreateForm(FormMapper $formMapper, BlockInterface $block)
    {
        $this->buildEditForm($formMapper, $block);
    }

    /**
     * {@inheritdoc}
     */
    /*
    CoreBundle and therefore ErrorElement removed
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
	    $errorElement
	        ->with('settings.title')
	            ->assertNotNull(array())
	            ->assertNotBlank()
	            ->assertMaxLength(array('limit' => 250))
	        ->end();
    }
    */

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
         	'title'	   			=> false,
          	'image'				=> false,
            'media'    			=> false,
            'context'  			=> false,
            'mediaId'  			=> null,
            'format'   			=> false,
         	'template'			=> '@App/Block/picture.html.twig',
        ));
    }

}
