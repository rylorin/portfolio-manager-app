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
    // strategies
    public const LONG_STOCK = 1;
    public const SHORT_STOCK = 2;
    public const LONG_CALL = 3;
    public const SHORT_CALL = 4;
    public const COVERED_CALL = 7;
    public const LONG_PUT = 5;
    public const SHORT_PUT = 6;
    public const THE_WHEEL = 8;
    public const RISK_REVERSAL = 9;
    public const BULL_SPREAD = 10;

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

    public function __toString( ): ?string {
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
        if (!$this->strategy) {
          return 'undefined';
        } elseif ($this->strategy == TradeUnit::LONG_STOCK) {
          return 'long stock';
        } elseif ($this->strategy == TradeUnit::SHORT_STOCK) {
          return 'short stock';
        } elseif ($this->strategy == TradeUnit::LONG_CALL) {
          return 'long call';
        } elseif ($this->strategy == TradeUnit::SHORT_CALL) {
          return 'naked short call';
        } elseif ($this->strategy == TradeUnit::LONG_PUT) {
          return 'long put';
        } elseif ($this->strategy == TradeUnit::SHORT_PUT) {
          return 'short put';
        } elseif ($this->strategy == TradeUnit::COVERED_CALL) {
          return 'covered short call';
        } elseif ($this->strategy == TradeUnit::THE_WHEEL) {
          return 'the wheel';
        } elseif ($this->strategy == TradeUnit::RISK_REVERSAL) {
          return 'risk reversal';
        } elseif ($this->strategy == TradeUnit::BULL_SPREAD) {
          return 'bull spread';
        } else {
          return strval($this->strategy);
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
      if (!$this->risk) $this->updateTradeUnit();
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

}
