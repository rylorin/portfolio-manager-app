<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Stock;
use App\Entity\Portfolio;
use App\Entity\Statement;
use App\Form\StockType;
use App\Repository\StockRepository;

/**
 * @Route("/stock")
 */
class StockController extends AbstractController
{

  const PAGE_ITEMS = 25;

  /**
   * @Route("/", name="repository_stock_index", methods={"GET"})
   */
  public function index(Request $request, PaginatorInterface $paginator, StockRepository $stockRepository): Response
  {
      $criteria = [];
      if ($request->query->getAlnum('symbol') != '') $criteria = array_merge($criteria, [ 's.symbol' => $request->query->get('symbol') ]);
      if ($request->query->getAlnum('name') != '') $criteria = array_merge($criteria, [ 's.name' => $request->query->get('name') ]);
      $data = $stockRepository->findStocks($criteria,  [ 's.symbol' => 'ASC' ]);
      $page = $request->query->getInt('page', 1);
      $stocks = $paginator->paginate($data, $page, self::PAGE_ITEMS);
      return $this->render('stock/index.html.twig', [
          'stocks' => $stocks,
          'data_count' => sizeof($data),
          'symbol' => $request->query->get('symbol'),
          'name' => $request->query->get('name'),
          'page' => $page,
      ]);
  }

  /**
   * @Route("/new", name="repository_stock_new", methods={"GET","POST"})
   */
  public function new(Request $request): Response
  {
      $stock = new Stock();
      $form = $this->createForm(StockType::class, $stock);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($stock);
          $entityManager->flush();

          return $this->redirectToRoute('repository_stock_index');
      }

      return $this->render('stock/new.html.twig', [
          'stock' => $stock,
          'form' => $form->createView(),
      ]);
  }

  /**
   * @Route("/{id}", name="repository_stock_show", methods={"GET"})
   */
  public function show(Stock $stock): Response
  {
      return $this->render('stock/show.html.twig', [
          'stock' => $stock,
      ]);
  }

  /**
   * @Route("/{id}/edit", name="repository_stock_edit", methods={"GET","POST"})
   */
  public function edit(Request $request, Stock $stock): Response
  {
      $form = $this->createForm(StockType::class, $stock);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $this->getDoctrine()->getManager()->flush();
          return $this->redirectToRoute('repository_stock_show', [ 'id' => $stock->getId() ]);
      }

      return $this->render('stock/edit.html.twig', [
          'stock' => $stock,
          'form' => $form->createView(),
      ]);
  }

  /**
   * @Route("/{id}", name="repository_stock_delete", methods={"DELETE"})
   */
  public function delete(Request $request, Stock $contract): Response
  {
      if ($this->isCsrfTokenValid('delete'.$contract->getId(), $request->request->get('_token'))) {
          $entityManager = $this->getDoctrine()->getManager();

          $positions = $contract->getPositions();
          foreach ($positions as $position) {
            $position->getPortfolio()->removePosition($position);
            $position->setPortfolio(null);
            $entityManager->remove($position);
          }
          // Should remove option contracts definitions too?
          $entityManager->remove($contract);

          $entityManager->flush();
      }

      return $this->redirectToRoute('repository_stock_index', [ 'page' => $request->query->getInt('page', 1) ]);
  }

}
