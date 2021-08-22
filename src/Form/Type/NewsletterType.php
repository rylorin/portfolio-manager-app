<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * Class NewsletterType
 *
 * This is the newsletter subscription/unsubscription form type
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class NewsletterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {
        $formBuilder
            ->add('email', EmailType::class, array(
                'attr' => array(
                    'placeholder' => "Email"
                )
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */

    public function getName()
    {
        return 'ryl_reigntheme_form_type_newsletter';
    }

}
