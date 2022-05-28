<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use App\Entity\Portfolio;
use App\Entity\Contract;
use App\Entity\Option;
use App\Entity\Statement;
use App\Entity\Stock;
use App\Entity\TradeUnit;
use App\Form\PortfolioType;
use App\Repository\PortfolioRepository;

/**
 * @Route("/portfolio")
 */
class PortfolioController extends AbstractController
{

    /**
     * @Route("/index", name="portfolio_index", methods={"GET"})
     */
    public function index(PortfolioRepository $portfolioRepository): Response
    {
        return $this->render('portfolio/index.html.twig', [
            'portfolios' => $portfolioRepository->findAll(),
        ]);
    }

    /**
     * @Route("/create", name="portfolio_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $portfolio = new Portfolio();
        $form = $this->createForm(PortfolioType::class, $portfolio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($portfolio);
            $entityManager->flush();
            return $this->redirectToRoute('portfolio_index');
        }

        return $this->render('portfolio/new.html.twig', [
            'portfolio' => $portfolio,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/dashboard", name="portfolio_show", methods={"GET"})
     */
    public function dashboard(Portfolio $portfolio): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
        $baserate['N/A'] = 0;
        $baserate[$portfolio->getBaseCurrency()] = 1;
        foreach ($currencies as $currency) {
            $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
        }
        $now = new \DateTime();
        $one_year_ago = (new \DateTime())->setDate($now->format('Y')-1, intval($now->format('m')), 1)->setTime(0, 0);
        $data = $entityManager->getRepository('App:Statement')->findSummaryByPortfolio($portfolio, $one_year_ago);
        $monthly = [];
        $count = 0;
        foreach ($data as $key => $value) {
            $month = $value->getDate()->format('Ym');
            if (!array_key_exists($month, $monthly)) {
                    $monthly[$month]['stocks'] = 0;
                    $monthly[$month]['options'] = 0;
                    $monthly[$month]['dividends'] = 0;
                    $monthly[$month]['interests'] = 0;
                    $monthly[$month]['month'] = $value->getDate()->format('m');
                    $monthly[$month]['year'] = $value->getDate()->format('Y');
                    $monthly[$month]['label'] = $value->getDate()->format('M-Y');
                    $monthly[$month]['count'] = 0;
            }
            if ($value->getStatementType() == Statement::TYPE_TRADE) {
                $monthly[$month]['stocks'] += $value->getRealizedPNL() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_TRADE_OPTION) {
                $monthly[$month]['options'] += $value->getRealizedPNL() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_DIVIDEND) {
                $monthly[$month]['dividends'] += $value->getAmount() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_TAX) {
                $monthly[$month]['dividends'] += $value->getAmount() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_INTEREST) {
                $monthly[$month]['interests'] += $value->getAmount() * $baserate[$value->getCurrency()];
            }
            $monthly[$month]['count']++;
            $count++;
        }
        ksort($monthly);
        return $this->render('portfolio/dashboard.html.twig', [
                'portfolio' => $portfolio,
                'monthly' => $monthly,
        ]);
    }

    /**
     * @Route("/{id}", name="portfolio_settings_show", methods={"GET"})
     */
    public function show(Portfolio $portfolio): Response
    {
        return $this->render('portfolio/show.html.twig', [
            'portfolio' => $portfolio,
        ]);
    }

    /**
     * @Route("/{id}/update", name="portfolio_settings_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Portfolio $portfolio): Response
    {
        $form = $this->createForm(PortfolioType::class, $portfolio);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('portfolio_settings_show', [ 'id' => $portfolio->getId() ]);
        }

        return $this->render('portfolio/edit.html.twig', [
            'portfolio' => $portfolio,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="portfolio_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Portfolio $portfolio): Response
    {
        if ($this->isCsrfTokenValid('delete'.$portfolio->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();

            $positions = $portfolio->getPositions();
            foreach ($positions as $position) {
              $portfolio->removePosition($position);
              $position->setPortfolio(null);
              $entityManager->remove($position);
            }

            $statements = $portfolio->getStatements();
            foreach ($statements as $statement) {
              $portfolio->removeStatement($statement);
              $statement->setPortfolio(null);
              $entityManager->remove($statement);
            }

            $balances = $portfolio->getBalances();
            foreach ($balances as $balance) {
              $portfolio->removeBalance($balance);
              $balance->setPortfolio(null);
              $entityManager->remove($balance);
            }

            $entityManager->remove($portfolio);
            $entityManager->flush();
        }
        return $this->redirectToRoute('portfolio_index');
    }

    /**
     * @Route("/{portfolio}/stock/{stock}/dashboard", name="portfolio_symbol_dashboard", methods={"GET"}, requirements={"portfolio":"\d+", "stock":"\d+"})
     * @ParamConverter("portfolio", class="App\Entity\Portfolio", options={"id" = "portfolio"})
     * @ParamConverter("stock", class="App\Entity\Stock", options={"id" = "stock"})
     */
    public function stock(Portfolio $portfolio, Stock $stock): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }

      return $this->render('portfolio/stock.html.twig', [
          'portfolio' => $portfolio,
          'currencies' => $baserate,
          'stock' => $stock,
          'positions' => $entityManager->getRepository('App:Position')->findByStock($portfolio, $stock),
          'statements' => $entityManager->getRepository('App:Statement')->findByDate(
            null, null,
            [ 'q.portfolio' => $portfolio, 'q.stock' => $stock ],
            [ 'q.date' => 'ASC' ]
          ),
          'open_trades' => $entityManager->getRepository('App:TradeUnit')->findTradeUnits(
            [ 'q.portfolio' => $portfolio, 'q.symbol' => $stock, 'q.status' => TradeUnit::OPEN_STATUS ],
            [ 'q.openingDate' => 'DESC' ]
          ),
          'closed_trades' => $entityManager->getRepository('App:TradeUnit')->findTradeUnits(
            [ 'q.portfolio' => $portfolio, 'q.symbol' => $stock, 'q.status' => TradeUnit::CLOSE_STATUS ],
            [ 'q.openingDate' => 'DESC' ]
          ),
      ]);
    }

    /**
     * @Route("/portfolio/{id}/symbols", name="portfolio_symbols", methods={"GET"})
     */
    public function symbols(Portfolio $portfolio): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
        $baserate['N/A'] = 0;
        $baserate[$portfolio->getBaseCurrency()] = 1;
        foreach ($currencies as $currency) {
            $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
        }
        $data = $entityManager->getRepository('App:Statement')->findSymbolsByPortfolio($portfolio);
        $stocks = [];
        $monthly = [];
        $count = 0;
        $stockId = null;
        foreach ($data as $key => $value) {
            if ($value->getStock()->getId() != $stockId) {
                if ($count) {
                    $stocks[] = [
                        'count' => $count,
                        'stockId' => $stockId,
                        'symbol' => $symbol,
                        'currency' => $currency,
                        'totalProceeds' => $totalProceeds,
                        'totalStockPNL' => $totalStockPNL,
                        'totalOptionsPNL' => $totalOptionsPNL,
                        'totalNetDividends' => $totalNetDividends,
                        'totalInterests' => $totalInterests,
                    ];
                }
                $count = 0;
                $stockId = $value->getStock()->getId();
                $symbol = $value->getStock()->getSymbol();
                $currency = $value->getStock()->getCurrency();
                $totalProceeds = 0;
                $totalStockPNL = 0;
                $totalOptionsPNL = 0;
                $totalNetDividends = 0;
                $totalInterests = 0;
            }
            $month = $value->getDate()->format('Ym');
            if (!array_key_exists($month, $monthly)) {
                    $monthly[$month]['stocks'] = 0;
                    $monthly[$month]['options'] = 0;
                    $monthly[$month]['dividends'] = 0;
                    $monthly[$month]['interests'] = 0;
                    $monthly[$month]['month'] = $value->getDate()->format('Y-m');
                    $monthly[$month]['year'] = $value->getDate()->format('Y');
                    $monthly[$month]['label'] = $value->getDate()->format('M-Y');
                    $monthly[$month]['count'] = 0;
            }
            if ($value->getStatementType() == Statement::TYPE_TRADE) {
                $totalProceeds += $value->getAmount();
                $totalStockPNL += $value->getRealizedPNL();
                $monthly[$month]['stocks'] += $value->getRealizedPNL() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_TRADE_OPTION) {
                $totalProceeds += $value->getAmount();
                $totalOptionsPNL += $value->getRealizedPNL();
                $monthly[$month]['options'] += $value->getRealizedPNL() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_DIVIDEND) {
                $totalProceeds += $value->getAmount();
                $totalNetDividends += $value->getAmount();
                $monthly[$month]['dividends'] += $value->getAmount() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_TAX) {
                $totalProceeds += $value->getAmount();
                $totalNetDividends += $value->getAmount();
                $monthly[$month]['dividends'] += $value->getAmount() * $baserate[$value->getCurrency()];
            } elseif ($value->getStatementType() == Statement::TYPE_INTEREST) {
                $totalInterests += $value->getAmount();
                $monthly[$month]['interests'] += $value->getAmount() * $baserate[$value->getCurrency()];
            }
            $monthly[$month]['count']++;
            $count++;
        }
        if ($count) {
            $stocks[] = [
                'count' => $count,
                'stockId' => $stockId,
                'symbol' => $symbol,
                'currency' => $currency,
                'totalProceeds' => $totalProceeds,
                'totalStockPNL' => $totalStockPNL,
                'totalOptionsPNL' => $totalOptionsPNL,
                'totalNetDividends' => $totalNetDividends,
                'totalInterests' => $totalInterests,
                ];
        }
        ksort($monthly);
        /*
        $now = intval((new \DateTime())->format('Ym'));
        foreach ($monthly as $key => $value) {
            if ($key < $now - 100) unset($monthly[$key]);
        }
        */
        return $this->render('portfolio/symbols.html.twig', [
  //                'currencies' => $baserate,
                'portfolio' => $portfolio,
//                'monthly' => $monthly,
                'stocks' => $stocks,
        ]);
    }

}
