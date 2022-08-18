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
use App\Entity\Contract;
use App\Repository\TradeUnitRepository;
use App\Form\TradeUnitType;

/**
 * @Route("/tradeunit")
 */
class TradeUnitController extends AbstractController
{

    private function computeStats($trades, $baserate) {
      $stats = [];
      foreach ($trades as $key => $trade) {
        $strategy = $trade->getStrategy();
        if (!array_key_exists($strategy, $stats)) {
          $stats[$strategy]['id'] = $strategy;
          $stats[$strategy]['count'] = 0;
          $stats[$strategy]['closed'] = 0;
          $stats[$strategy]['pnl'] = 0.0;
          $stats[$strategy]['win'] = 0;
          $stats[$strategy]['lost'] = 0;
          $stats[$strategy]['strategy'] = $trade->getStrategyName();
          $stats[$strategy]['min'] = null;
          $stats[$strategy]['max'] = null;
          $stats[$strategy]['duration'] = 0;
        }
        $stats[$strategy]['count']++;
        if ($trade->getStatus() == TradeUnit::CLOSE_STATUS) {
          $pnl = $trade->getPnL() * $baserate[$trade->getCurrency()];
          $stats[$strategy]['closed']++;
          $stats[$strategy]['pnl'] += $pnl;
          if ($trade->getPnL() > 0) {
            $stats[$strategy]['win']++;
          } else {
            $stats[$strategy]['lost']++;
          }
          if (!$stats[$strategy]['min'] || ($pnl < $stats[$strategy]['min'])) $stats[$strategy]['min'] = $pnl;
          if (!$stats[$strategy]['max'] || ($pnl > $stats[$strategy]['max'])) $stats[$strategy]['max'] = $pnl;
          $stats[$strategy]['duration'] += $trade->getDuration();
        }
      }

      usort($stats, function($a, $b) {
        return $b['count'] - $a['count'];
      });
      return $stats;
    }

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
      $closed_trades = $entityManager->getRepository('App:TradeUnit')->findTradeUnits(
        [ 'q.portfolio' => $portfolio, 'q.status' => TradeUnit::CLOSE_STATUS ],
        [ 'q.closingDate' => 'DESC' ]
      );
      $open_trades = $entityManager->getRepository('App:TradeUnit')->findTradeUnits(
        [ 'q.portfolio' => $portfolio, 'q.status' => TradeUnit::OPEN_STATUS ],
        [ 'q.openingDate' => 'DESC' ]
      );

      $stats = $this->computeStats($closed_trades, $baserate);

      return $this->render('tradeunit/index.html.twig', [
          'portfolio' => $portfolio,
          'currencies' => $baserate,
          'closed_trades' => $closed_trades,
          'open_trades' => $open_trades,
          'stats' => $stats,
      ]);
    }

    /**
     * @Route("/portfolio/{portfolio}/strategy/{strategy}", name="portfolio_trades_strategy", methods={"GET"})
     */
      public function strategy(TradeUnitRepository $repository, Portfolio $portfolio, int $strategy): Response
      {
        $entityManager = $this->getDoctrine()->getManager();
        $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
        $baserate['N/A'] = 0;
        $baserate[$portfolio->getBaseCurrency()] = 1;
        foreach ($currencies as $currency) {
            $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
        }
        $closed_trades = $entityManager->getRepository('App:TradeUnit')->findTradeUnits(
          [ 'q.portfolio' => $portfolio, 'q.status' => TradeUnit::CLOSE_STATUS, 'q.strategy' => $strategy ],
          [ 'q.closingDate' => 'DESC' ]
        );
        $open_trades = $entityManager->getRepository('App:TradeUnit')->findTradeUnits(
          [ 'q.portfolio' => $portfolio, 'q.status' => TradeUnit::OPEN_STATUS, 'q.strategy' => $strategy ],
          [ 'q.openingDate' => 'DESC' ]
        );
  
        $stats = $this->computeStats($closed_trades, $baserate);

        return $this->render('tradeunit/index.html.twig', [
            'portfolio' => $portfolio,
            'currencies' => $baserate,
            'closed_trades' => $closed_trades,
            'open_trades' => $open_trades,
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
      $checksums = [];
      foreach ($tradeunit->getOpeningTrades() as $key => $statement) {
        $contract = $statement->getContract();
        if ($contract) {
          $symbol = $contract->getSymbol();
          if (!array_key_exists($symbol, $checksums)) {
            $checksums[$symbol]['symbol'] = $symbol;
            $checksums[$symbol]['name'] = $contract->getName();
            $checksums[$symbol]['count'] = 0;
          }
          $checksums[$symbol]['count'] += $statement->getQuantity();
        }
      }
      return $this->render('tradeunit/show.html.twig', [
          'portfolio' => $tradeunit->getPortfolio(),
          'tradeunit' => $tradeunit,
          'checksums' => $checksums,
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
     * @Route("/{id}", name="tradeunit_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TradeUnit $tradeunit): Response
    {
        $portfolio = $tradeunit->getPortfolio();
        if ($this->isCsrfTokenValid('delete'.$tradeunit->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();

            $statements = $tradeunit->getOpeningTrades();
            foreach ($statements as $statement) {
              $statement->setTradeUnit(null);
            }
            // Should remove option contracts definitions too?
            $entityManager->remove($tradeunit);

            $entityManager->flush();
        }
        return $this->redirectToRoute('portfolio_trades_index', [ 'id' => $portfolio->getId() ]);
    }

}
