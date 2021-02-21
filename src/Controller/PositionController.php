<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Stock;
use App\Entity\Option;
use App\Entity\Position;
use App\Entity\Portfolio;
use App\Entity\Contract;
use App\Form\PositionType;
use App\Repository\PositionRepository;

/**
 * @Route("/position")
 */
class PositionController extends AbstractController
{
    /**
     * @Route("/portfolio/{id}/index", name="portfolio_positions_index", methods={"GET"})
     */
    public function index(PositionRepository $positionRepository, Portfolio $portfolio): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
      $positions = $entityManager->getRepository('App:Position')->findBySecType(
        null,
        [ 'p.portfolio' => $portfolio ],
        [ 'c.symbol' => 'ASC' ]
      );
      return $this->render('position/index.html.twig', [
        'portfolio' => $portfolio,
        'currencies' => $baserate,
        'positions' => $positions,
      ]);
    }

    /**
     * @Route("/portfolio/{id}/stocks", name="portfolio_stocks_index", methods={"GET","POST"})
     */
    public function stocks(Request $request, Portfolio $portfolio): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
//      $balances = $entityManager->getRepository('App:Balance')->findByPortfolio($portfolio);
      $stocks = $entityManager->getRepository('App:Position')->findBySecType(
        Contract::TYPE_STOCK,
        [ 'p.portfolio' => $portfolio ],
        [ 'c.symbol' => 'ASC' ]
      );
      return $this->render('position/stocks.html.twig', [
        'portfolio' => $portfolio,
        'currencies' => $baserate,
        'stocks' => $stocks,
      ]);
    }

    /**
     * @Route("/portfolio/{id}/options", name="portfolio_options_index", methods={"GET","POST"})
     */
    public function options(Request $request, Portfolio $portfolio): Response
    {
      $entityManager = $this->getDoctrine()->getManager();
      $currencies = $entityManager->getRepository('App:Currency')->findByBase($portfolio->getBaseCurrency());
      $baserate['N/A'] = 0;
      $baserate[$portfolio->getBaseCurrency()] = 1;
      foreach ($currencies as $currency) {
          $baserate[$currency->getCurrency()] = 1.0 / $currency->getRate();
      }
//      $balances = $entityManager->getRepository('App:Balance')->findByPortfolio($portfolio);
      $options = $entityManager->getRepository('App:Position')->findByOption(
              [ 'p.portfolio' => $portfolio ],
              [ 'o.lastTradeDate' => 'ASC', 'c.symbol' => 'ASC', 'o.callOrPut' => 'ASC', 'o.strike' => 'DESC' ]);
      return $this->render('position/options.html.twig', [
        'portfolio' => $portfolio,
        'currencies' => $baserate,
        'options' => $options,
      ]);
    }

    /**
     * @Route("/portfolio/{id}/new", name="portfolio_position_new", methods={"GET","POST"})
     */
    public function new(Request $request, Portfolio $portfolio): Response
    {
        $position = new Position();
        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
          $position->setPortfolio($portfolio);
          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($position);
          $entityManager->flush();

          return $this->redirectToRoute('portfolio_positions_index', [ 'id' => $position->getPortfolio()->getId() ]);
        }

        return $this->render('position/new.html.twig', [
          'portfolio' => $portfolio,
          'position' => $position,
          'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/read", name="portfolio_position_show", methods={"GET"})
     */
    public function show(Position $position): Response
    {
    	$contract = $position->getContract();
    	$stock = null;
    	$option = null;
    	if ($contract instanceof Stock) {
    		$type = 'Stock';
    		$stock = $contract;
    	}
    	elseif ($contract instanceof Option) {
    		$type = 'Option';
    		$option = $contract;
    		$stock = $option->getStock();
    	} else {
    		$type = print_r($position->getContract(), true);
    	}
    	return $this->render('position/show.html.twig', [
            'portfolio' => $position->getPortfolio(),
    		'position' => $position,
    		'type' => $type,
    		'stock' => $stock,
    		'option' => $option,
    	]);
    }

    /**
     * @Route("/{id}/update", name="portfolio_position_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Position $position): Response
    {
        $form = $this->createForm(PositionType::class, $position);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('portfolio_position_show', [ 'id' => $position->getId() ]);
        }

        $contract = $position->getContract();
        if ($contract instanceof Stock) {
          $type = 'Stock';
        }
        elseif ($contract instanceof Option) {
          $type = 'Option';
        } else {
          $type = print_r($position->getContract(), true);
        }
        return $this->render('position/edit.html.twig', [
            'portfolio' => $position->getPortfolio(),
    	    'position' => $position,
    		'type' => $type,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="portfolio_position_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Position $position): Response
    {
        $id = $position->getPortfolio()->getId();
        if ($this->isCsrfTokenValid('delete'.$position->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $position->getPortfolio()->removePosition($position);
            $position->getContract()->removePosition($position);
            $entityManager->flush();
        }
        return $this->redirectToRoute('portfolio_positions_index', [ 'id' => $id ] );
    }
}
