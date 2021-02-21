<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use App\Entity\TradeUnit;
use App\Entity\Portfolio;
use App\Repository\TradeUnitRepository;
use App\Form\TradeUnitType;

/**
 * @Route("/tradeunit")
 */
class TradeUnitController extends AbstractController
{

  /**
   * @Route("/portfolio/{id}/index", name="portfolio_trades_index", methods={"GET"})
   */
    public function index(TradeUnitRepository $repository, Portfolio $portfolio): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
      $trades = $entityManager->getRepository('App:TradeUnit')->findTradeUnits(
        [ 'q.portfolio' => $portfolio ],
        [ 'q.openingDate' => 'DESC' ]
      );
      $stats = [];
      foreach ($trades as $key => $trade) {
        $strategy = $trade->getStrategyName();
        if (!array_key_exists($strategy, $stats)) {
          $stats[$strategy]['count'] = 0;
          $stats[$strategy]['closed'] = 0;
          $stats[$strategy]['pnl'] = 0.0;
          $stats[$strategy]['win'] = 0;
          $stats[$strategy]['lost'] = 0;
        }
        $stats[$strategy]['count']++;
        if ($trade->getStatus() == TradeUnit::CLOSE_STATUS) {
          $stats[$strategy]['closed']++;
          $stats[$strategy]['pnl'] += $trade->getPnL() * $baserate[$trade->getCurrency()];
          if ($trade->getPnL() > 0) {
            $stats[$strategy]['win']++;
          } else {
            $stats[$strategy]['lost']++;
          }
        }
      }

      return $this->render('tradeunit/index.html.twig', [
          'portfolio' => $portfolio,
          'currencies' => $baserate,
          'trades' => $trades,
          'stats' => $stats,
      ]);
    }

    /**
     * @Route("/{tradeunit<\d+>}", name="portfolio_tradeunit_show", methods={"GET"})
     * @ParamConverter("tradeunit", class="App\Entity\TradeUnit", options={"id" = "tradeunit"})
     */
    public function show(TradeUnit $tradeunit): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($tradeunit->getPortfolio()->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$tradeunit->getPortfolio()->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
      return $this->render('tradeunit/show.html.twig', [
          'portfolio' => $tradeunit->getPortfolio(),
          'tradeunit' => $tradeunit,
          'currencies' => $baserate,
      ]);
    }

    /**
     * @Route("/{tradeunit<\d+>}/edit", name="portfolio_tradeunit_edit", methods={"GET","POST"})
     * @ParamConverter("tradeunit", class="App\Entity\TradeUnit", options={"id" = "tradeunit"})
     */
    public function edit(Request $request, TradeUnit $tradeunit): Response
    {
      $form = $this->createForm(TradeUnitType::class, $tradeunit);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $this->getDoctrine()->getManager()->flush();
          return $this->redirectToRoute('portfolio_tradeunit_show', [ 'tradeunit' => $tradeunit->getId() ]);
      }

      return $this->render('tradeunit/edit.html.twig', [
        'portfolio' => $tradeunit->getPortfolio(),
        'tradeunit' => $tradeunit,
        'form' => $form->createView(),
      ]);
    }

    /**
     * @Route("/{id}", name="portfolio_tradeunit_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TradeUnit $tradeunit): Response
    {
        $portfolio = $tradeunit->getPortfolio();
        if ($this->isCsrfTokenValid('delete'.$tradeunit->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();

            $statements = $tradeunit->getOpeningTrades();
            foreach ($statements as $statement) {
              $statement->setTrade(null);
            }
            // Should remove option contracts definitions too?
            $entityManager->remove($tradeunit);

            $entityManager->flush();
        }
        return $this->redirectToRoute('portfolio_trades_index', [ 'id' => $portfolio->getId() ]);
    }

}
