<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Option;
use App\Form\OptionType;
use App\Repository\OptionRepository;

/**
 * @Route("/option")
 */
class OptionController extends AbstractController
{

  const PAGE_ITEMS = 25;

  /**
   * @Route("/index", name="repository_option_index", methods={"GET"})
   */
  public function index(Request $request, PaginatorInterface $paginator, OptionRepository $optionRepository): Response
  {
    $data = $optionRepository->findOptions(
      [ 'o.symbol' => $request->query->getAlnum('symbol'), ],
      [
        's.symbol' => 'ASC', 'o.lastTradeDate' => 'ASC', 'o.strike' => 'ASC',
      ]);
    $page = $request->query->getInt('page', 1);
    $options = $paginator->paginate($data, $page, self::PAGE_ITEMS);
    return $this->render('option/index.html.twig', [
        'options' => $options,
        'data_count' => sizeof($data),
        'symbol' => $request->query->getAlnum('symbol'),
        'page' => $page,
    ]);
  }

  /**
   * @Route("/create", name="repository_option_new", methods={"GET","POST"})
   */
  public function new(Request $request): Response
  {
      $option = new Option();
      $form = $this->createForm(OptionType::class, $option);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($option);
          $entityManager->flush();

          return $this->redirectToRoute('repository_option_index');
      }

      return $this->render('option/new.html.twig', [
          'option' => $option,
          'form' => $form->createView(),
      ]);
  }

  /**
   * @Route("/{id}/read", name="repository_option_show", methods={"GET"})
   */
  public function show(Option $option): Response
  {
      return $this->render('option/show.html.twig', [
          'option' => $option,
          'stock' => $option->getStock(),
      ]);
  }

  /**
   * @Route("/{id}/update", name="repository_option_edit", methods={"GET","POST"})
   */
  public function edit(Request $request, Option $option): Response
  {
      $form = $this->createForm(OptionType::class, $option);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $this->getDoctrine()->getManager()->flush();

          return $this->redirectToRoute('repository_option_index');
      }

      return $this->render('option/edit.html.twig', [
          'option' => $option,
          'form' => $form->createView(),
      ]);
  }

  /**
   * @Route("/{id}/delete", name="repository_option_delete", methods={"DELETE"})
   */
  public function delete(Request $request, Option $option): Response
  {
      if ($this->isCsrfTokenValid('delete'.$option->getId(), $request->request->get('_token'))) {
          $entityManager = $this->getDoctrine()->getManager();

          $positions = $option->getPositions();
          foreach ($positions as $position) {
            $position->getPortfolio()->removePosition($position);
            $position->setPortfolio(null);
            $entityManager->remove($position);
          }
          $option->getStock()->removeOption($option);

//            $entityManager->remove($option);
          $entityManager->flush();
      }
      return $this->redirectToRoute('repository_option_index', [ 'page' => $request->query->getInt('page', 1) ]);
  }

  /**
   * @Route("/opportunities", name="repository_opportunities", methods={"GET"})
   */
  public function opportunities(Request $request, PaginatorInterface $paginator, OptionRepository $optionRepository): Response
  {
    $data = $optionRepository->findOptions(
      null,
      [
        's.symbol' => 'ASC', 'o.lastTradeDate' => 'ASC', 'o.strike' => 'ASC',
      ]);
    foreach ($data as $key => $contract) {
      if ($contract->getMoneyDepth() < 0
        && $contract->getDaysToMaturity() <= 8
        && ($contract->getBidYieldToMaturity() * 360) > 0.2
        && $contract->getBid() > 0.5
        ) {
        // this is a short opportunity to consider
      } else {
        unset($data[$key]);
      }
    }
    $page = $request->query->getInt('page', 1);
    $options = $paginator->paginate($data, $page, self::PAGE_ITEMS);
    return $this->render('option/index.html.twig', [
        'options' => $options,
        'data_count' => sizeof($data),
        'page' => $page,
    ]);
  }

}
