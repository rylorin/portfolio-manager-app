<?php

namespace App\Block;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\NewsBundle\Block\RecentPostsBlockService;
use Sonata\Form\Type\ImmutableArrayType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SidePopularPostsBlockService
 *
 * Renders a block
 *
 */
class SidePopularPostsBlockService extends RecentPostsBlockService
{

	/**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        parent::configureSettings($resolver);
    	$resolver->setDefault('template', '@App/Block/posts_side.html.twig');
    }

}
