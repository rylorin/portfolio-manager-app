<?php

namespace App\Entity;

use App\Repository\TradeParameterRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TradeParameterRepository::class)
 * @ORM\Table(name="trading_parameters")
 */
class TradeParameter
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class)
     * @ORM\JoinColumn(nullable=false, unique=true))
     */
    private $stock;

    /**
     * @ORM\ManyToOne(targetEntity=Portfolio::class, inversedBy="tradingParameters")
     * @ORM\JoinColumn(nullable=false)
     */
    private $portfolio;

    /**
     * @ORM\Column(type="float")
     */
    private $NavRatio;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): self
    {
        $this->portfolio = $portfolio;

        return $this;
    }

    public function getNavRatio(): ?float
    {
        return $this->NavRatio;
    }

    public function setNavRatio(float $NavRatio): self
    {
        $this->NavRatio = $NavRatio;

        return $this;
    }
}
