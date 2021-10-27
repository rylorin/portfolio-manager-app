<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OptionRepository;
use App\Entity\Contract;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OptionRepository::class)
 */
class Option extends Contract
{
    protected static $moneyLabelMapping = [
      'ATM' => 'At-the-Money',
      'OTM' => 'Out-of-the-Money',
      'ITM' => 'In-the-Money'
    ];

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="options", cascade={"persist"})
     * @ORM\JoinColumn(name="stock_id", referencedColumnName="id", nullable=false)
     */
    private $stock;

    /**
     * @ORM\Column(type="date", nullable=false)
     */
    private $lastTradeDate;

    /**
     * @ORM\Column(type="float", nullable=false)
     */
    private $strike;

    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    private $callOrPut;

    /**
     * @ORM\Column(type="integer")
     */
    private $multiplier;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ImpliedVolatility;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Delta;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pvDividend;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Gamma;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Vega;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Theta;

    public function __construct()
    {
        parent::__construct();
        $this->multiplier = 100;
    }

    public function __toString()
    {
        return $this->getSymbol();
    }

    public function getSecType(): string {
      return Contract::TYPE_OPTION;
    }

    public static function formatSymbol(string $symbol, \DateTime $maturity, float $strike, string $type): string {
        return sprintf(
            "%s %s %.1f %s",
            str_replace('.T', '', str_replace(' ', '-', $symbol)),
            strtoupper($maturity->format("dMy")),
            $strike,
            $type
        );
    }

    private function updateSymbol(): void
    {
        if (/* 17-03-2021 !$this->getSymbol() && */ $this->stock && $this->lastTradeDate && $this->strike && $this->callOrPut) {
            $this->setSymbol($this::formatSymbol(
                $this->stock->getSymbol(),
                $this->lastTradeDate,
                $this->strike,
                $this->callOrPut
            ));
        }
    }

    public function getStrike(): ?float
    {
        return $this->strike;
    }

    public function setStrike(?float $strike): self
    {
        $this->strike = $strike;
        $this->updateSymbol();
        return $this;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;
        $this->currency = $stock ? $stock->getCurrency() : null;
        $this->updateSymbol();
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
        return $this;
    }

    public function getDaysToMaturity(): float
    {
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
        return $days;
    }

    public function getCallOrPut(): ?string
    {
        return $this->callOrPut;
    }

    public function setCallOrPut(string $callOrPut): self
    {
        $this->callOrPut = $callOrPut;
        $this->updateSymbol();
        return $this;
    }

    public function getType(): ?string
    {
        return $this->callOrPut;
    }

    public function setType(string $callOrPut): self
    {
    	$this->callOrPut = $callOrPut;
    	$this->updateSymbol();
    	return $this;
    }

    public function getYahooTicker(): ?string
    {
        if ($this->getStock()) {
          return sprintf('%s%.6s%s%08d',
            $this->getStock()->getYahooTicker(), $this->getLastTradeDate()->format("ymd"), $this->getType(), $this->getStrike() * 1000);
        } else {
          return sprintf("InvalidId(%d)", $this->getId());
        }
    }
/*
    public function getExchange(): ?string
    {
        $s = parent::getExchange();
        if ($s) {
            return $s;
        } elseif ($this->getStock()) {
            return $this->getStock()->getExchange();
        } else {
          return null;
        }
    }

    public function getDescription(): ?string {
      return $this->stock->getDescription();
    }
*/
    public function getMultiplier(): ?int
    {
        return $this->multiplier ? $this->multiplier : 100;
    }

    public function setMultiplier(int $multiplier): self
    {
        $this->multiplier = $multiplier;
        return $this;
    }

    public function getMoneyDepth(): ?float {
      if ($this->getType() == 'C') {
        return $this->stock->getPrice() - $this->getStrike();
      } else if ($this->getType() == 'P') {
        return $this->getStrike() - $this->stock->getPrice();
      } else {
        return null;
      }
    }

    public function getMoneyDepthPercent(): ?float {
        $md = $this->getMoneyDepth();
        if ($md < 0) {
          return ($this->stock->getPrice() ? ($md / $this->stock->getPrice()) : null);
        } elseif ($md > 0) {
          return ($md / $this->strike);
        } else {
          return 0;
        }
    }

    public function getMoneyShortLabel(): ?string {
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

    public function getMoneyLongLabel(): ?string {
      return ($this->getMoneyShortLabel() ? self::$moneyLabelMapping[$this->getMoneyShortLabel()] : null);
    }
/*
    public function getName(): ?string {
      return $this->stock ? $this->stock->getName() : null;
    }
*/
    public function getYieldToMaturity(): ?float {
      return $this->getPrice() / $this->strike / $this->getDaysToMaturity() * 360;
    }

    public function getBidYieldToMaturity(): ?float {
      return $this->getBid() / $this->strike / $this->getDaysToMaturity() * 360;
    }

    public function getImpliedVolatility(): ?float
    {
        return $this->ImpliedVolatility;
    }

    public function setImpliedVolatility(?float $ImpliedVolatility): self
    {
        $this->ImpliedVolatility = $ImpliedVolatility;

        return $this;
    }

    public function getDelta(): ?float
    {
        return $this->Delta;
    }

    public function setDelta(?float $Delta): self
    {
        $this->Delta = $Delta;

        return $this;
    }

    public function getPvDividend(): ?float
    {
        return $this->pvDividend;
    }

    public function setPvDividend(?float $pvDividend): self
    {
        $this->pvDividend = $pvDividend;

        return $this;
    }

    public function getGamma(): ?float
    {
        return $this->Gamma;
    }

    public function setGamma(?float $Gamma): self
    {
        $this->Gamma = $Gamma;

        return $this;
    }

    public function getVega(): ?float
    {
        return $this->Vega;
    }

    public function setVega(?float $Vega): self
    {
        $this->Vega = $Vega;

        return $this;
    }

    public function getTheta(): ?float
    {
        return $this->Theta;
    }

    public function setTheta(?float $Theta): self
    {
        $this->Theta = $Theta;

        return $this;
    }

}
