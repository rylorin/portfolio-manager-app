<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TradeUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Statement;

/**
 * @ORM\Entity(repositoryClass=TradeUnitRepository::class)
 */
class TradeUnit
{
    // statuses
    public const OPEN_STATUS = 1;
    public const CLOSE_STATUS = 2;
    // stock strategies
    public const LONG_STOCK = 1;
    public const SHORT_STOCK = 2;
    // options strategies
    public const LONG_CALL = 3;
    public const SHORT_CALL = 4;
    public const LONG_PUT = 5;
    public const SHORT_PUT = 6;
    public const COVERED_CALL = 7;
    public const THE_WHEEL = 8;
    public const RISK_REVERSAL = 9;
    public const BULL_SPREAD = 10;
    public const SHORT_STRANGLE = 11;
    public const BEAR_SPREAD = 12;
    public const LONG_STRANGLE = 13;
    public const LONG_STRADDLE = 14;
    public const SHORT_STRADDLE = 15;
    public const FRONT_RATIO_SPREAD = 16;

    public const stategyLabelMapping = [
      TradeUnit::LONG_STOCK => 'long stock',
      TradeUnit::SHORT_STOCK => 'short stock',
      TradeUnit::LONG_CALL => 'long call',
      TradeUnit::SHORT_CALL => 'naked short call',
      TradeUnit::COVERED_CALL => 'covered short call',
      TradeUnit::LONG_PUT => 'long put',
      TradeUnit::SHORT_PUT => 'short put',
      TradeUnit::THE_WHEEL => 'the wheel',
      TradeUnit::RISK_REVERSAL => 'risk reversal',
      TradeUnit::BULL_SPREAD => 'bull spread',
      TradeUnit::BEAR_SPREAD => 'bear spread',
      TradeUnit::FRONT_RATIO_SPREAD => 'front ratio spread',
      TradeUnit::LONG_STRANGLE => 'long strangle',
      TradeUnit::SHORT_STRANGLE => 'short strangle',
      TradeUnit::LONG_STRADDLE => 'long straddle',
      TradeUnit::SHORT_STRADDLE => 'short straddle',
    ];

    public const stategyMenuMapping = [
      'undefined' => 0,
      'long stock' => TradeUnit::LONG_STOCK,
      'short stock' => TradeUnit::SHORT_STOCK,
      'long call' => TradeUnit::LONG_CALL,
      'naked short call' => TradeUnit::SHORT_CALL,
      'covered short call' => TradeUnit::COVERED_CALL,
      'long put' => TradeUnit::LONG_PUT,
      'short put' => TradeUnit::SHORT_PUT,
      'the wheel' => TradeUnit::THE_WHEEL,
      'risk reversal' => TradeUnit::RISK_REVERSAL,
      'bull spread' => TradeUnit::BULL_SPREAD,
      'bear spread' => TradeUnit::BEAR_SPREAD,
      'front ratio spread' => TradeUnit::FRONT_RATIO_SPREAD,
      'short strangle' => TradeUnit::SHORT_STRANGLE,
      'long strangle' => TradeUnit::LONG_STRANGLE,
      'long straddle' => TradeUnit::LONG_STRADDLE,
      'short straddle' => TradeUnit::SHORT_STRADDLE,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     */
    private $strategy;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $openingDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closingDate;

    /**
     * @ORM\OneToMany(targetEntity=Statement::class, mappedBy="tradeUnit")
     */
    private $openingTrades;

    /**
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $PnL;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="tradeUnits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $symbol;

    /**
     * @ORM\ManyToOne(targetEntity=Portfolio::class, inversedBy="tradeUnits")
     */
    private $portfolio;

    /**
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    private $currency;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $risk;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comment;

    public function __construct(Statement $statement = null)
    {
        $this->openingTrades = new ArrayCollection();
        $this->closingTrades = new ArrayCollection();

        if ($statement) {
          $this->setPortfolio($statement->getPortfolio());
          $this->setSymbol($statement->getStock());
          $this->setCurrency($statement->getCurrency());
          $this->addOpeningTrade($statement);
          $statement->setTradeUnit($this);
          /* guess a default strategy */
          if ($statement->getStatementType() == Statement::TYPE_TRADE) {
            if ($statement->getQuantity() > 0) {
              $this->setStrategy(TradeUnit::LONG_STOCK);
            } else {
              $this->setStrategy(TradeUnit::SHORT_STOCK);
            }
          } elseif ($statement->getStatementType() == Statement::TYPE_TRADE_OPTION) {
            $contract = $statement->getContract();
            if ($statement->getQuantity() > 0) {
              if ($contract->getCallOrPut() == 'C') {
                $this->setStrategy(TradeUnit::LONG_CALL);
              } else {
                $this->setStrategy(TradeUnit::LONG_PUT);
              }
            } else {
              if ($contract->getCallOrPut() == 'C') {
                $this->setStrategy(TradeUnit::SHORT_CALL);
              } else {
                $this->setStrategy(TradeUnit::SHORT_PUT);
              }
            }
          }
        }
    }

    public function __toString( ): string {
      return $this->symbol . '@' . $this->openingDate->format('d-m-y H:i') . ' (' . strval($this->id) . ')';
    }

    private function updateTradeUnit(): self {
      $openingDate = null;
      $closingDate = null;
      $qty = 0;
      $this->PnL = 0;
      $this->risk = null;
      $risk = 0;
      foreach ($this->openingTrades as $key => $statement) {
        $this->PnL += $statement->getRealizedPNL();
        $qty += $statement->getQuantity();
        if ((!$openingDate) || ($statement->getDate() < $openingDate)) $openingDate = $statement->getDate();
        if ($statement->getDate() > $closingDate) $closingDate = $statement->getDate();
        // short put strategy to the wheel if we also have stocks
        if (($this->strategy == TradeUnit::SHORT_PUT) && ($statement->getStatementType() == Statement::TYPE_TRADE)) $this->strategy = TradeUnit::THE_WHEEL;
        if ($statement->getStatementType() == Statement::TYPE_TRADE_OPTION) {
          $risk += $statement->getContract()->getStrike() * $statement->getContract()->getMultiplier() * $statement->getQuantity();
          $this->risk = max(abs($risk), $this->risk);
        } elseif ($statement->getStatementType() == Statement::TYPE_TRADE) {
          $risk += $statement->getPrice() * $statement->getQuantity();
          $this->risk = max(abs($risk), $this->risk);
        }
      }
      $this->openingDate = ($openingDate ? $openingDate : new \DateTime());
      if ($qty) {
        $this->status = TradeUnit::OPEN_STATUS;
        $this->closingDate = null;
      } else {
        $this->status = TradeUnit::CLOSE_STATUS;
        $this->closingDate = $closingDate;
      }
      if ($this->status == TradeUnit::OPEN_STATUS) $this->closingDate = null;
      return $this;
    }

    public function getDuration(): int {
      if ($this->closingDate) {
        $maturity = $this->closingDate->diff($this->openingDate);
      } else {
        $maturity = (new \DateTime())->diff($this->openingDate);
      }
      return $maturity->days + 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStrategy(): ?int
    {
        return $this->strategy;
    }

    public function getStrategyName(): string
    {
      if (array_key_exists($this->strategy, self::stategyLabelMapping)) {
        return self::stategyLabelMapping[$this->strategy];
      } else {
        return sprintf("stategy(%d)", $this->strategy);
      }
    }

    public function setStrategy(int $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    public function getOpeningDate(): ?\DateTimeInterface
    {
        return $this->openingDate;
    }

    public function setOpeningDate(\DateTimeInterface $openingDate): self
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closingDate;
    }

    public function setClosingDate(?\DateTimeInterface $closingDate): self
    {
        $this->closingDate = $closingDate;

        return $this;
    }

    /**
     * @return Collection|Statement[]
     */
    public function getOpeningTrades(): Collection
    {
      $iterator = $this->openingTrades->getIterator();
      $iterator->uasort(function ($a, $b) {
        if ($a->getDate() == $b->getDate()) {
          return 0;
        } elseif ($a->getDate() < $b->getDate()) return -1;
        else return 1;
      });
      $this->openingTrades = new ArrayCollection(iterator_to_array($iterator));
      return $this->openingTrades;
    }

    public function addOpeningTrade(Statement $openingTrade): self
    {
        if (!$this->openingTrades->contains($openingTrade)) {
            $this->openingTrades[] = $openingTrade;
            $openingTrade->setTradeUnit($this);
        }
        $this->updateTradeUnit();
        return $this;
    }

    public function removeOpeningTrade(Statement $openingTrade): self
    {
        if ($this->openingTrades->removeElement($openingTrade)) {
            // set the owning side to null (unless already changed)
            if ($openingTrade->getTradeUnit() === $this) {
                $openingTrade->setTradeUnit(null);
            }
        }
        $this->updateTradeUnit();
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getStatusText(): string
    {
        if ($this->status == TradeUnit::OPEN_STATUS) {
          return 'Open';
        } elseif ($this->status == TradeUnit::CLOSE_STATUS) {
          return 'Closed';
        } else {
          return 'N/A';
        }
    }

    public function isClosed(): bool {
      return ($this->status == TradeUnit::CLOSE_STATUS ? true : false);
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPnL(): ?float
    {
        return $this->PnL;
    }

    public function setPnL(?float $PnL): self
    {
        $this->PnL = $PnL;

        return $this;
    }

    public function isWinning(): bool {
      return ($this->PnL > 0);
    }

    public function getSymbol(): ?Stock
    {
        return $this->symbol;
    }

    public function setSymbol(?Stock $symbol): self
    {
        $this->symbol = $symbol;

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

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getRisk(): ?int
    {
      // if (!$this->risk) $this->updateTradeUnit();
      return (int)$this->risk;
    }

    public function setRisk(?int $risk): self
    {
        $this->risk = $risk;
        return $this;
    }

    public function getRoR(): ?float
    {
      return ($this->risk ? $this->PnL / $this->risk : null);
    }

    public function getAnnualRoR(): ?float
    {
      if ($this->risk) {
        return ($this->getDuration() < 7 ? $this->PnL / $this->risk / $this->getDuration() * 250 : $this->PnL / $this->risk / $this->getDuration() * 360);
      } else {
        return null;
      }
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

}
