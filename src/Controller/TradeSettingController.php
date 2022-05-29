<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\TradeParameter;
use App\Form\TradeParameterType;
use App\Repository\TradeParameterRepository;
use App\Entity\Portfolio;

/**
 * @Route("/setting")
 */
class TradeSettingController extends AbstractController
{
    /**
     * @Route("/", name="portfolio_settings_tradingsettings_index", methods={"GET"})
     */
    public function index(TradeParameterRepository $TradeParameterRepository): Response
    {
        return $this->render('TradeParameter/index.html.twig', [
            'currencies' => $TradeParameterRepository->findAllTradeParameter([ 'q.base' => 'ASC', 'q.TradeParameter' => 'ASC' ]),
        ]);
    }

    /**
     * @Route("/new/{id}", name="portfolio_settings_tradingsettings_new", methods={"GET","POST"})
     */
    public function new(Request $request, Portfolio $portfolio): Response
    {
        $TradeParameter = new TradeParameter();
        $TradeParameter->setPortfolio($portfolio);
        $form = $this->createForm(TradeParameterType::class, $TradeParameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($TradeParameter);
            $entityManager->flush();

            return $this->redirectToRoute('portfolio_settings_show', [ 'id' => $portfolio->getId() ]);
        }

        return $this->render('tradingsettings/new.html.twig', [
            'setting' => $TradeParameter,
            'form' => $form->createView(),
            'portfolio' => $portfolio,
        ]);
    }

    /**
     * @Route("/{id}", name="portfolio_settings_tradingsettings_show", methods={"GET"})
     */
    public function show(TradeParameter $TradeParameter): Response
    {
        return $this->render('tradingsettings/show.html.twig', [
            'setting' => $TradeParameter,
            'portfolio' => $TradeParameter->getPortfolio(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="portfolio_settings_tradingsettings_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, TradeParameter $TradeParameter): Response
    {
        $form = $this->createForm(TradeParameterType::class, $TradeParameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('portfolio_settings_show', [ 'id' => $TradeParameter->getPortfolio()->getId() ]);
        }

        return $this->render('tradingsettings/edit.html.twig', [
            'setting' => $TradeParameter,
            'form' => $form->createView(),
            'portfolio' => $TradeParameter->getPortfolio(),
        ]);
    }

    /**
     * @Route("/{id}", name="portfolio_settings_tradingsettings_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TradeParameter $TradeParameter): Response
    {
        if ($this->isCsrfTokenValid('delete'.$TradeParameter->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($TradeParameter);
            $entityManager->flush();
        }

    return $this->redirectToRoute('portfolio_settings_show', [ 'id' => $TradeParameter->getPortfolio()->getId() ]);
    }
}
