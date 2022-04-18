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
    public const ROLLSTRATEGIES = [
        'Off' => 0,
        'Defensive' => 1,
        'Agressive' => 2,
    ];
    public const ROLLSTRATEGIES_REV = [
        null => 'Off',
        0 => 'Off',
        1 => 'Defensive',
        2 => 'Agressive',
    ];
    public const CCSTRATEGIES = [
        'Off' => 0,
        'On' => 1,
    ];
    public const CCSTRATEGIES_REV = [
        null => 'Off',
        0 => 'Off',
        1 => 'On',
    ];
    public const CSPSTRATEGIES = [
        'Off' => 0,
        'On' => 1,
    ];
    public const CSPSTRATEGIES_REV = [
        null => 'Off',
        0 => 'Off',
        1 => 'On',
    ];

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
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $cspStrategy;

    /**
     * @ORM\Column(type="float")
     */
    private $navRatio;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $rollStrategy;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $ccStrategy;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="createdAt")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="updatedAt")
     */
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPortfolio(): ?Portfolio
    {
        return $this->portfolio;
    }

    public function setPortfolio(?Portfolio $portfolio): self
    {
        $this->portfolio = $portfolio;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCspStrategy(): ?int
    {
        return $this->cspStrategy;
    }

    public function setCspStrategy(int $cspStrategy): self
    {
        $this->cspStrategy = $cspStrategy;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCspStrategyName(): ?string
    {
        return TradeParameter::CSPSTRATEGIES_REV[$this->cspStrategy];
    }

    public function getNavRatio(): ?float
    {
        return $this->navRatio;
    }

    public function setNavRatio(float $NavRatio): self
    {
        $this->navRatio = $NavRatio;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRollStrategy(): ?int
    {
        return $this->rollStrategy;
    }

    public function setRollStrategy(int $rollStrategy): self
    {
        $this->rollStrategy = $rollStrategy;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRollStrategyName(): ?string
    {
        return TradeParameter::ROLLSTRATEGIES_REV[$this->rollStrategy];
    }

    public function getCcStrategy(): ?int
    {
        return $this->ccStrategy;
    }

    public function setCcStrategy(int $ccStrategy): self
    {
        $this->ccStrategy = $ccStrategy;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCcStrategyName(): ?string
    {
        return TradeParameter::CCSTRATEGIES_REV[$this->ccStrategy];
    }

}