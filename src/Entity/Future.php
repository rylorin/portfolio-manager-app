<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FutureRepository;
use App\Entity\Contract;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FutureRepository::class)
 * @ORM\Table(name="future")
 */
class Future extends Contract
{

  /**
   * @ORM\Column(type="date", nullable=false)
   */
  private $lastTradeDate;

  /**
   * @ORM\Column(type="integer")
   */
  private $multiplier;

  /**
   * @ORM\ManyToOne(targetEntity=Contract::class, cascade={"persist"})
   * @ORM\JoinColumn(name="underlying_id", referencedColumnName="id", nullable=false)
   */
  private $underlying;

  public function __construct(?string $symbol = null)
  {
    parent::__construct($symbol);
    $this->multiplier = 50;
    $this->createdAt = new \DateTime();
  }

  public function __toString()
  {
    return $this->getSymbol();
  }

  public function getSecType(): string
  {
    return Contract::TYPE_FUTURE;
  }

  public static function formatSymbol(string $symbol, \DateTime $maturity, float $strike, string $type): string
  {
    return sprintf(
      "%s %s",
      str_replace('.T', '', str_replace(' ', '-', $symbol)),
      strtoupper($maturity->format("dMy")),
    );
  }

  // public function getUpdatedAt(): ?\DateTimeInterface
  // {
  //     return $this->updatedAt;
  // }

  // private function setUpdatedAt(?\DateTimeInterface $updated): self
  // {
  //     $this->updatedAt = $updated;
  //     return $this;
  // }

  private function updateSymbol(): void
  {
    if ( /* 17-03-2021 !$this->getSymbol() && */$this->underlying && $this->lastTradeDate) {
      $this->setSymbol($this::formatSymbol(
        $this->stock->getSymbol(),
        $this->lastTradeDate,
      )
      );
    }
  }

  // public function getStrike(): ?float
  // {
  //     return $this->strike;
  // }

  // public function setStrike(?float $strike): self
  // {
  //     $this->strike = $strike;
  //     $this->updateSymbol();
  //     $this->updatedAt = new \DateTime();
  //     return $this;
  // }

  public function getUnderlying(): ? Stock
  {
    return $this->underlying;
  }

  public function setUnderlying(? Contract $stock): self
  {
    $this->underlying = $stock;
    $this->currency = $stock ? $stock->getCurrency() : null;
    $this->updateSymbol();
    $this->updatedAt = new \DateTime();
    return $this;
  }

  public function getLastTradeDate(): ?\DateTimeInterface
  {
    return $this->lastTradeDate;
  }

  public function setLastTradeDate(\DateTimeInterface $lastTradeDateOrContractMonth): self
  {
    $this->lastTradeDate = $lastTradeDateOrContractMonth;
    $this->updateSymbol();
    $this->updatedAt = new \DateTime();
    return $this;
  }

  public function getDaysToMaturity(): float
  {
    if ($this->lastTradeDate) {
      $maturity = (new \DateTime())->diff($this->lastTradeDate);
      if ($maturity->invert) {
        if ($maturity->days > 1) {
          $days = -$maturity->days;
        } else {
          $days = 1;
        }
      } else {
        $days = $maturity->days + ($maturity->h / 24.0) + 1.0;
      }
    } else {
      // TODO: invalid value!
      $days = 0;
    }
    return $days;
  }

  public function getYahooTicker(): ?string
  {
    if ($this->getStock()) {
      return sprintf(
        '%s%.6s%s%08d',
        $this->getStock()->getYahooTicker(), $this->getLastTradeDate()->format("ymd"), $this->getType(), $this->getStrike() * 1000
      );
    } else {
      return sprintf("InvalidId(%d)", $this->getId());
    }
  }

  public function getMultiplier(): int
  {
    return $this->multiplier ? $this->multiplier : 50;
  }

  public function setMultiplier(int $multiplier): self
  {
    $this->multiplier = $multiplier;
    $this->updatedAt = new \DateTime();
    return $this;
  }

  public function getMoneyDepth(): ?float
  {
    if ($this->getType() == 'C') {
      return $this->stock->getPrice() - $this->getStrike();
    } else if ($this->getType() == 'P') {
      return $this->getStrike() - $this->stock->getPrice();
    } else {
      return null;
    }
  }

  public function getMoneyDepthPercent(): ?float
  {
    $md = $this->getMoneyDepth();
    if ($md < 0) {
      return ($this->stock->getPrice() ? ($md / $this->stock->getPrice()) : null);
    } elseif ($md > 0) {
      return ($md / $this->strike);
    } else {
      return 0;
    }
  }

  public function getMoneyShortLabel(): ?string
  {
    if (!$this->stock->getPrice()) {
      return null;
    } elseif (($this->getMoneyDepth() / $this->stock->getPrice()) > 0.01) {
      return 'OTM';
    } else if (($this->getMoneyDepth() / $this->stock->getPrice()) < -0.01) {
      return 'ITM';
    } else {
      return 'ATM';
    }
  }

  public function getMoneyLongLabel(): ?string
  {
    return ($this->getMoneyShortLabel() ? self::$moneyLabelMapping[$this->getMoneyShortLabel()] : null);
  }

  public function getYieldToMaturity(): ?float
  {
    return $this->getPrice() / $this->strike / $this->getDaysToMaturity() * 360;
  }

  public function getBidYieldToMaturity(): ?float
  {
    return $this->getBid() / $this->strike / $this->getDaysToMaturity() * 360;
  }

  public function getImpliedVolatility(): ?float
  {
    return $this->ImpliedVolatility;
  }

  public function setImpliedVolatility(?float $ImpliedVolatility): self
  {
    $this->ImpliedVolatility = $ImpliedVolatility;
    $this->updatedAt = new \DateTime();
    return $this;
  }

}