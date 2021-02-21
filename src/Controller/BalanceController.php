<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Balance;
use App\Entity\Portfolio;
use App\Form\BalanceType;
use App\Repository\BalanceRepository;

/**
 * @Route("/balance")
 */
class BalanceController extends AbstractController
{
    /**
     * @Route("/portfolio/{id}/index", name="portfolio_balances_index", methods={"GET"})
     */
    public function index(BalanceRepository $balanceRepository, Portfolio $portfolio): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
      $balances = $entityManager->getRepository('App:Balance')->findByPortfolio($portfolio, [ 'currency' => 'ASC' ]);
      return $this->render('balance/index.html.twig', [
              'currencies' => $baserate,
              'portfolio' => $portfolio,
              'balances' => $balances,
      ]);
    }

    /**
     * @Route("/create/portfolio/{id}", name="portfolio_balance_new", methods={"GET","POST"})
     */
    public function new(Request $request, Portfolio $portfolio): Response
    {
        $balance = new Balance();
        $form = $this->createForm(BalanceType::class, $balance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
          $balance->setPortfolio($portfolio);
          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($balance);
          $entityManager->flush();
          return $this->redirectToRoute('portfolio_balances_index', [ 'id' => $balance->getPortfolio()->getId() ]);
        }

        return $this->render('balance/new.html.twig', [
          'portfolio' => $portfolio,
          'balance' => $balance,
          'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/read", name="portfolio_balance_show", methods={"GET"})
     */
    public function show(Balance $balance): Response
    {
        return $this->render('balance/show.html.twig', [
          'portfolio' => $balance->getPortfolio(),
          'balance' => $balance,
        ]);
    }

    /**
     * @Route("/{id}/update", name="portfolio_balance_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Balance $balance): Response
    {
        $form = $this->createForm(BalanceType::class, $balance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('portfolio_balances_index', [ 'id' => $balance->getPortfolio()->getId() ]);
        }

        return $this->render('balance/edit.html.twig', [
          'portfolio' => $balance->getPortfolio(),
          'balance' => $balance,
          'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="portfolio_balance_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Balance $balance): Response
    {
        if ($this->isCsrfTokenValid('delete'.$balance->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->remove($balance);
            $id = $balance->getPortfolio()->getId();
            $balance->getPortfolio()->removeBalance($balance);
            $entityManager->flush();
        }
        return $this->redirectToRoute('portfolio_balances_index', [ 'id' => $id ] );
    }
}
