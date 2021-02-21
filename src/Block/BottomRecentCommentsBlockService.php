<?php

namespace App\Block;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\NewsBundle\Block\RecentCommentsBlockService;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class BottomRecentCommentsBlockService
 *
 * Renders a block
 *
 */
class BottomRecentCommentsBlockService extends RecentCommentsBlockService
{

	/**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        parent::configureSettings($resolver);
    	$resolver->setDefault('template', '@App/Block/comments_bottom.html.twig');
    }

}
