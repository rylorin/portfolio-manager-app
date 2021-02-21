<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Currency;
use App\Form\CurrencyType;
use App\Repository\CurrencyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/currency")
 */
class CurrencyController extends AbstractController
{
    /**
     * @Route("/", name="repository_currency_index", methods={"GET"})
     */
    public function index(CurrencyRepository $currencyRepository): Response
    {
        return $this->render('currency/index.html.twig', [
            'currencies' => $currencyRepository->findAllCurrency([ 'q.base' => 'ASC', 'q.currency' => 'ASC' ]),
        ]);
    }

    /**
     * @Route("/new", name="repository_currency_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $currency = new Currency();
        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($currency);
            $entityManager->flush();

            return $this->redirectToRoute('repository_currency_index');
        }

        return $this->render('currency/new.html.twig', [
            'currency' => $currency,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="repository_currency_show", methods={"GET"})
     */
    public function show(Currency $currency): Response
    {
        return $this->render('currency/show.html.twig', [
            'currency' => $currency,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="repository_currency_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Currency $currency): Response
    {
        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('repository_currency_index');
        }

        return $this->render('currency/edit.html.twig', [
            'currency' => $currency,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="repository_currency_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Currency $currency): Response
    {
        if ($this->isCsrfTokenValid('delete'.$currency->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($currency);
            $entityManager->flush();
        }

        return $this->redirectToRoute('repository_currency_index');
    }
}
