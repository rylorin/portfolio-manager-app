<?php

namespace App\Controller;

use App\Entity\OpenOrder;
use App\Form\OpenOrderType;
use App\Repository\OpenOrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/open/order")
 */
class OpenOrderController extends AbstractController
{
    /**
     * @Route("/", name="open_order_index", methods={"GET"})
     */
    public function index(OpenOrderRepository $openOrderRepository): Response
    {
        return $this->render('open_order/index.html.twig', [
            'open_orders' => $openOrderRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="open_order_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $openOrder = new OpenOrder();
        $form = $this->createForm(OpenOrderType::class, $openOrder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($openOrder);
            $entityManager->flush();

            return $this->redirectToRoute('open_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('open_order/new.html.twig', [
            'open_order' => $openOrder,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="open_order_show", methods={"GET"})
     */
    public function show(OpenOrder $openOrder): Response
    {
        return $this->render('open_order/show.html.twig', [
            'open_order' => $openOrder,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="open_order_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, OpenOrder $openOrder): Response
    {
        $form = $this->createForm(OpenOrderType::class, $openOrder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('open_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('open_order/edit.html.twig', [
            'open_order' => $openOrder,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="open_order_delete", methods={"POST"})
     */
    public function delete(Request $request, OpenOrder $openOrder): Response
    {
        if ($this->isCsrfTokenValid('delete'.$openOrder->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($openOrder);
            $entityManager->flush();
        }

        return $this->redirectToRoute('open_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
