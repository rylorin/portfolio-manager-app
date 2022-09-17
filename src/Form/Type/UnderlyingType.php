<?php
declare(strict_types=1);
namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TradeUnit;

class UnderlyingType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $underlying[''] = null;
        $items = $this->entityManager->getRepository('App:Future')->findAll();
        foreach ($items as $item) {
            $underlying[$item->getSymbol()] = $item;
        }
        $items = $this->entityManager->getRepository('App:Stock')->findAll();
        foreach ($items as $item) {
            $underlying[$item->getSymbol()] = $item;
        }
        $resolver->setDefaults([
            'choices' => $underlying,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

}