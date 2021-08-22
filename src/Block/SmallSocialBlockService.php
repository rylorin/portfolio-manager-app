<?php

namespace App\Block;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SmallSocialBlockService
 *
 * Renders a block
 *
 */
class SmallSocialBlockService extends BaseBlockService
{

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        return $this->renderResponse($blockContext->getTemplate(), array(
            'block_context'  => $blockContext,
            'block'          => $blockContext->getBlock(),
            'settings'  	 => $blockContext->getSettings(),
        ), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('settings', ImmutableArrayType::class, array(
	        'keys' => array(
	                array('facebook', TextType::class, array('required' => false)),
	                array('twitter', TextType::class, array('required' => false)),
	                array('pinterest', TextType::class, array('required' => false)),
	                array('linkedin', TextType::class, array('required' => false)),
	        )
	    ));
    }

    /**
     * {@inheritdoc}
     */
    /*
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
	    $errorElement
	        ->with('settings.title')
	            ->assertNotNull(array())
	            ->assertNotBlank()
	            ->assertMaxLength(array('limit' => 50))
	        ->end();
    }
    */

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'facebook' 			=> false,
            'twitter' 			=> false,
            'pinterest'			=> false,
            'linkedin' 			=> false,
        	'template' 			=> '@App/Block/social-sm.html.twig',
        	// Deprecated settings
        		'behance' 			=> false,
        		'google' 			=> false,
        ));
    }

}
