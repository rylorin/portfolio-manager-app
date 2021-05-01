<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PositionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PositionRepository::class)
 */
class Position
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Portfolio::class, inversedBy="positions", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * should not be null but required to remove position
     */
    private $portfolio;

    /**
     * @ORM\Column(type="date")
     */
    private $openDate;

    /**
     * @ORM\ManyToOne(targetEntity=Contract::class, inversedBy="positions", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $contract;

    /**
     * @ORM\Column(type="float")
     */
    private $quantity;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cost;

    public function __construct()
    {
        $this->openDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): self
    {
        $this->contract = $contract;
        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(float $pos): self
    {
        $this->quantity = $pos;
        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): self
    {
        $this->cost = $cost;
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

    public function getOpenDate(): ?\DateTimeInterface
    {
        return $this->openDate;
    }

    public function setOpenDate(\DateTimeInterface $openDate): self
    {
        $this->openDate = $openDate;
        return $this;
    }

    public function getValue(): ?float
    {
        return $this->contract->getPrice() * $this->quantity * $this->contract->getMultiplier();
    }

    public function getPRU(): ?float
    {
        return $this->quantity ? ($this->cost / $this->quantity / $this->contract->getMultiplier()) : null;
    }

    public function getPNL(): ?float
    {
        return $this->quantity ? ($this->getValue() - $this->cost) : null;
    }

    public function getPNLYield(): ?float
    {
        return ($this->quantity && $this->cost) ? ($this->quantity / abs($this->quantity) * $this->getPNL() / $this->cost) : null;
    }

    private function getDaysToMaturity(): ?int
    {
        $maturity = $this->openDate->diff($this->contract->getLastTradeDate());
        if ($maturity->invert) {
          if ($maturity->days) {
            $days = -$maturity->days;
          } else {
            $days = 1;
          }
        } else {
          $days = $maturity->days + 2;
        }
        return $days;
    }

    public function getYield(): ?float
    {
      if ($this->contract->getSecType() == Contract::TYPE_STOCK) {
        return $this->quantity ? $this->quantity / abs($this->quantity) * $this->contract->getDividendYield() : 0;
      } elseif ($this->contract->getSecType() == Contract::TYPE_OPTION) {
        return $this->quantity ? -$this->quantity / abs($this->quantity) * $this->getPRU() / $this->contract->getStrike() / $this->getDaysToMaturity() * 360 : 0;
      } else {
        return null;
      }
    }
}
