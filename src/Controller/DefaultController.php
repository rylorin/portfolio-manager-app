<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{

    /**
     * Newsletter subscription action
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newsletterAction(Request $request)
    {
    	$form = $this->createForm('AppBundle\Form\Type\NewsletterType');
    	$form->handleRequest($request);

    	if ($form->isValid()) {
    		$template = 'confirmation';
    		$message = 'ryl.reigntheme.block.newsletter.message.confirmation';
    		try {
    			$mc = $this->get('hype_mailchimp');
    			$data = $mc->getList()
    				->subscribe($form->get('email')->getData(), 'html', false);
    			/*
	    		foreach ($data as $i => $value) {
	    			$message = $message . $i . " => " . $data[$i] . ", ";
	    		}
	    		*/
			} catch (\Exception $e) {
				$template = 'mcerror';
    			$message = $e->getMessage();
			}
    	} else {
    		$template = 'formerror';
    		$message = 'ryl.reigntheme.block.newsletter.message.formerror';
    	}

    	return $this->render('@App/Block/newsletter_' . $template . '.html.twig', array(
    			'message' => $message
    	));
    }

}
