<?php

namespace App\Controller;

use App\Entity\Exchange;
use App\Form\ExchangeType;
use App\Repository\ExchangeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/exchange")
 */
class ExchangeController extends AbstractController
{
    /**
     * @Route("/", name="exchange_index", methods={"GET"})
     */
    public function index(ExchangeRepository $exchangeRepository): Response
    {
        return $this->render('exchange/index.html.twig', [
            'exchanges' => $exchangeRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="exchange_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $exchange = new Exchange();
        $form = $this->createForm(ExchangeType::class, $exchange);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($exchange);
            $entityManager->flush();

            return $this->redirectToRoute('exchange_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('exchange/new.html.twig', [
            'exchange' => $exchange,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="exchange_show", methods={"GET"})
     */
    public function show(Exchange $exchange): Response
    {
        return $this->render('exchange/show.html.twig', [
            'exchange' => $exchange,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="exchange_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Exchange $exchange): Response
    {
        $form = $this->createForm(ExchangeType::class, $exchange);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('exchange_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('exchange/edit.html.twig', [
            'exchange' => $exchange,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="exchange_delete", methods={"POST"})
     */
    public function delete(Request $request, Exchange $exchange): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exchange->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($exchange);
            $entityManager->flush();
        }

        return $this->redirectToRoute('exchange_index', [], Response::HTTP_SEE_OTHER);
    }
}
