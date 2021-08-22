<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use App\Entity\Statement;
use App\Entity\OptionTradeStatement;
use App\Entity\Portfolio;
use App\Entity\Stock;
use App\Entity\TradeUnit;
use App\Form\StatementType;
use App\Form\TradeStatementType;
use App\Form\OptionTradeStatementType;
use App\Repository\StatementRepository;
use App\Repository\TradeOptionRepository;

/**
 * @Route("/statement")
 */
class StatementController extends AbstractController
{
    /**
     * @Route("/portfolio/{id}/index", name="portfolio_statements_index", methods={"GET"}, requirements={"id":"\d+"})
     */
    public function index(StatementRepository $statementRepository, Portfolio $portfolio): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
      $data = $entityManager->getRepository('App:Statement')->findSummaryByPortfolio($portfolio);
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
      return $this->render('statement/summary.html.twig', [
              'portfolio' => $portfolio,
              'monthly' => $monthly,
      ]);

/*
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
        return $this->render('statement/index.html.twig', [
          'portfolio' => $portfolio,
          'currencies' => $baserate,
          'statements' => $statementRepository->findByPortfolio($portfolio->getId(), [ 'date' => 'ASC' ]),
        ]);
*/
    }

    /**
     * @Route("/portfolio/{portfolio}/stock/{stock}/history", name="portfolio_statements_stock", methods={"GET"}, requirements={"portfolio":"\d+", "stock":"\d+"})
     * @ParamConverter("portfolio", class="App\Entity\Portfolio", options={"id" = "portfolio"})
     * @ParamConverter("stock", class="App\Entity\Stock", options={"id" = "stock"})
     */
    public function stockHistory(StatementRepository $statementRepository, Portfolio $portfolio, Stock $stock): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
        return $this->render('statement/index.html.twig', [
          'portfolio' => $portfolio,
          'currencies' => $baserate,
          'statements' => $statementRepository->findByDate(
              null, null,
              [ 'q.portfolio' => $portfolio, 'q.stock' => $stock ],
              [ 'q.date' => 'ASC' ]
              )
        ]);
    }

    /**
     * @Route("/portfolio/{portfolio}/history/{year}/{month}", name="portfolio_statements_history", methods={"GET"}, requirements={"portfolio":"\d+", "year":"\d+", "month":"\d+"})
     * @ParamConverter("portfolio", class="App\Entity\Portfolio", options={"id" = "portfolio"})
     */
    public function monthHistory(StatementRepository $statementRepository, Portfolio $portfolio, int $year, int $month): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
      $from = new \DateTime(sprintf("%04d-%02d-%02d", $year, $month, 1));
      $to = (new \DateTime(sprintf("%04d-%02d-%02d 23:59:59", $month == 12 ? $year + 1 : $year, $month == 12 ? 1 : $month + 1, 1)))->sub(new \DateInterval('P1D'));
      $statements = $statementRepository->findByDate(
        $from, $to,
        [ 'q.portfolio' => $portfolio ],
        [ 'q.date' => 'ASC' ]
      );
      return $this->render('statement/index.html.twig', [
          'portfolio' => $portfolio,
          'currencies' => $baserate,
          'statements' => $statements,
      ]);
    }

    /**
     * @Route("/create", name="statement_new", methods={"GET","POST"})
     */
    public function new(Request $request, Portfolio $portfolio): Response
    {
        $statement = new Statement();
        $form = $this->createForm(StatementType::class, $statement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($statement);
            $entityManager->flush();

            return $this->redirectToRoute('portfolio_statements_index', [ 'id' => $position->getPortfolio()->getId() ]);
        }

        return $this->render('statement/new.html.twig', [
            'statement' => $statement,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="portfolio_statement_show", methods={"GET"})
     */
    public function show(Statement $statement): Response
    {
        return $this->render('statement/show.html.twig', [
            'portfolio' => $statement->getPortfolio(),
            'statement' => $statement,
        ]);
    }

    /**
     * @Route("/{id}/update", name="portfolio_statement_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Statement $statement): Response
    {
      if ($statement->getStatementType() == Statement::TYPE_TRADE) {
        $form = $this->createForm(TradeStatementType::class, $statement);
      } elseif ($statement->getStatementType() == Statement::TYPE_TRADE_OPTION) {
        $form = $this->createForm(OptionTradeStatementType::class, $statement);
      } else {
        $form = $this->createForm(StatementType::class, $statement);
      }
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $this->getDoctrine()->getManager()->flush();

          return $this->redirectToRoute('portfolio_statement_show', [ 'id' => $statement->getId() ]);
      }

      return $this->render('statement/edit.html.twig', [
          'portfolio' => $statement->getPortfolio(),
          'statement' => $statement,
          'form' => $form->createView(),
      ]);
    }

    /**
     * @Route("/{id}/delete", name="statement_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Statement $statement): Response
    {
        $id = $statement->getPortfolio()->getId();
        if ($this->isCsrfTokenValid('delete'.$statement->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($statement);
            $entityManager->flush();
        }
        return $this->redirectToRoute('portfolio_statements_index', [ 'id' => $id ]);
    }

    /**
     * @Route("/{id}/createtradeunit", name="portfolio_statement_createtradeunit", methods={"GET"})
     */
    public function createtradeunit(Statement $statement): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $tu = new TradeUnit($statement);
      $entityManager->persist($tu);
      $entityManager->flush();
      return $this->render('statement/show.html.twig', [
        'portfolio' => $statement->getPortfolio(),
        'statement' => $statement,
      ]);
    }

    /**
     * @Route("/{id}/unlinktradeunit", name="portfolio_statement_unlinktradeunit", methods={"GET"})
     */
    public function unlinktradeunit(Statement $statement): Response
    {
      $statement->setTradeUnit(null);
      $this->getDoctrine()->getManager()->flush();
      return $this->render('statement/show.html.twig', [
        'portfolio' => $statement->getPortfolio(),
        'statement' => $statement,
      ]);
    }

    /**
     * @Route("/{id}/linktradeunit/{tradeunit}", name="portfolio_statement_linktradeunit", methods={"GET"})
     */
    public function linktradeunit(Statement $statement, TradeUnit $tradeunit): Response
    {
      $statement->setTradeUnit($tradeunit);
      $this->getDoctrine()->getManager()->flush();
      return $this->render('statement/show.html.twig', [
        'portfolio' => $statement->getPortfolio(),
        'statement' => $statement,
      ]);
    }

    /**
     * @Route("/{id}/guesstradeunit", name="portfolio_statement_guesstradeunit", methods={"GET"})
     */
    public function guesstradeunit(StatementRepository $statementRepository, Statement $statement): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $portfolio = $statement->getPortfolio();
      if ($statement->getStatementType() == Statement::TYPE_TRADE_OPTION) {
        $x = $entityManager->getRepository('App:OptionTradeStatement')->findPreviousStatementForSymbol(
          $portfolio, $statement->getDate(), $statement->getContract());
        if ($x) $statement->setTradeUnit($x->getTradeUnit());
      }
      elseif (($statement->getStatementType() == Statement::TYPE_TRADE)
        || ($statement->getStatementType() == Statement::TYPE_DIVIDEND)
        || ($statement->getStatementType() == Statement::TYPE_TAX)
        ) {
        $x = $statementRepository->findPreviousStatementForSymbol(
          $portfolio,
          $statement->getDate(),
          $statement->getStock()
        );
        if ($x) $statement->setTradeUnit($x->getTradeUnit());
      }
      $entityManager->flush();
      return $this->render('statement/show.html.twig', [
        'portfolio' => $statement->getPortfolio(),
        'statement' => $statement,
      ]);
    }
}
