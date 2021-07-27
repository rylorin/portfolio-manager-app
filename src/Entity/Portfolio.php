<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\User\User;
use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\TradeParameter;

/**
 * @ORM\Entity(repositoryClass=PortfolioRepository::class)
 */
class Portfolio
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=31, nullable=false)
     */
    private $account;

    /**
     * @ORM\Column(type="string", length=3, nullable=false)
     */
    private $baseCurrency;

    /**
     * @ORM\OneToMany(targetEntity=Position::class, mappedBy="portfolio", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $positions;

    /**
     * @ORM\OneToMany(targetEntity=Balance::class, mappedBy="portfolio", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $balances;

    /**
     * @ORM\OneToMany(targetEntity=Statement::class, mappedBy="portfolio", orphanRemoval=true)
     */
    private $statements;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=TradeUnit::class, mappedBy="portfolio")
     */
    private $tradeUnits;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="portfolios")
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=OpenOrder::class, mappedBy="Account", orphanRemoval=true)
     */
    private $openOrders;

    /**
     * @ORM\ManyToOne(targetEntity=Stock::class)
     */
    private $benchmark;

    /**
     * @ORM\OneToMany(targetEntity=TradeParameter::class, mappedBy="portfolio", orphanRemoval=true)
     */
    private $tradingParameters;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $PutRatio;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $sellNakedPutSleep;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $FindSymbolsSleep;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $AdjustCashSleep;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $rollOptionsSleep;

    public function __construct()
    {
        $this->positions = new ArrayCollection();
        $this->balances = new ArrayCollection();
        $this->statements = new ArrayCollection();
        $this->tradeUnits = new ArrayCollection();
        $this->openOrders = new ArrayCollection();
        $this->tradingParameters = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->account;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?string
    {
        return $this->account;
    }

    public function setAccount(string $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getBaseCurrency(): ?string
    {
        return $this->baseCurrency;
    }

    public function setBaseCurrency(?string $baseCurrency): self
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->baseCurrency;
    }

    public function setCurrency(?string $baseCurrency): self
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    /**
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function addPosition(Position $position): self
    {
        if (!$this->positions->contains($position)) {
            $this->positions[] = $position;
            $position->setPortfolio($this);
        }

        return $this;
    }

    public function removePosition(Position $position): self
    {
        if ($this->positions->contains($position)) {
            $this->positions->removeElement($position);
            // set the owning side to null (unless already changed)
            if ($position->getPortfolio() === $this) {
                $position->setPortfolio(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Balance[]
     */
    public function getBalances(): Collection
    {
        return $this->balances;
    }

    public function addBalance(Balance $balance): self
    {
        if (!$this->balances->contains($balance)) {
            $this->balances[] = $balance;
            $balance->setPortfolio($this);
        }

        return $this;
    }

    public function removeBalance(Balance $balance): self
    {
        if ($this->balances->contains($balance)) {
            $this->balances->removeElement($balance);
            // set the owning side to null (unless already changed)
            if ($balance->getPortfolio() === $this) {
                $balance->setPortfolio(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Statement[]
     */
    public function getStatements(): Collection
    {
        return $this->statements;
    }

    public function addStatement(Statement $statement): self
    {
        if (!$this->statements->contains($statement)) {
            $this->statements[] = $statement;
            $statement->setPortfolio($this);
        }

        return $this;
    }

    public function removeStatement(Statement $statement): self
    {
        if ($this->statements->contains($statement)) {
            $this->statements->removeElement($statement);
            // set the owning side to null (unless already changed)
            if ($statement->getPortfolio() === $this) {
                $statement->setPortfolio(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|TradeUnit[]
     */
    public function getTradeUnits(): Collection
    {
        return $this->tradeUnits;
    }

    public function addTradeUnit(TradeUnit $tradeUnit): self
    {
        if (!$this->tradeUnits->contains($tradeUnit)) {
            $this->tradeUnits[] = $tradeUnit;
            $tradeUnit->setPortfolio($this);
        }

        return $this;
    }

    public function removeTradeUnit(TradeUnit $tradeUnit): self
    {
        if ($this->tradeUnits->removeElement($tradeUnit)) {
            // set the owning side to null (unless already changed)
            if ($tradeUnit->getPortfolio() === $this) {
                $tradeUnit->setPortfolio(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection|OpenOrder[]
     */
    public function getOpenOrders(): Collection
    {
        return $this->openOrders;
    }

    public function addOpenOrder(OpenOrder $openOrder): self
    {
        if (!$this->openOrders->contains($openOrder)) {
            $this->openOrders[] = $openOrder;
            $openOrder->setAccount($this);
        }

        return $this;
    }

    public function removeOpenOrder(OpenOrder $openOrder): self
    {
        if ($this->openOrders->removeElement($openOrder)) {
            // set the owning side to null (unless already changed)
            if ($openOrder->getAccount() === $this) {
                $openOrder->setAccount(null);
            }
        }

        return $this;
    }

    public function getBenchmark(): ?Stock
    {
        return $this->benchmark;
    }

    public function setBenchmark(?Stock $benchmark): self
    {
        $this->benchmark = $benchmark;

        return $this;
    }

    /**
     * @return Collection|TradeParameter[]
     */
    public function getTradingParameters(): ?Collection
    {
        return $this->tradingParameters;
    }

    public function addTradingParameter(TradeParameter $tradingParameter): self
    {
        if (!$this->tradingParameters->contains($tradingParameter)) {
            $this->tradingParameters[] = $tradingParameter;
            $tradingParameter->setPortfolio($this);
        }

        return $this;
    }

    public function removeTradingParameter(TradeParameter $tradingParameter): self
    {
        if ($this->tradingParameters->removeElement($tradingParameter)) {
            // set the owning side to null (unless already changed)
            if ($tradingParameter->getPortfolio() === $this) {
                $tradingParameter->setPortfolio(null);
            }
        }

        return $this;
    }

    public function getPutRatio(): ?float
    {
        return $this->PutRatio;
    }

    public function setPutRatio(?float $PutRatio): self
    {
        $this->PutRatio = $PutRatio;

        return $this;
    }

    public function getSellNakedPutSleep(): ?int
    {
        return $this->sellNakedPutSleep;
    }

    public function setSellNakedPutSleep(int $sellNakedPutSleep): self
    {
        $this->sellNakedPutSleep = $sellNakedPutSleep;

        return $this;
    }

    public function getFindSymbolsSleep(): ?int
    {
        return $this->FindSymbolsSleep;
    }

    public function setFindSymbolsSleep(?int $FindSymbolsSleep): self
    {
        $this->FindSymbolsSleep = $FindSymbolsSleep;

        return $this;
    }

    public function getAdjustCashSleep(): ?int
    {
        return $this->AdjustCashSleep;
    }

    public function setAdjustCashSleep(?int $AdjustCashSleep): self
    {
        $this->AdjustCashSleep = $AdjustCashSleep;

        return $this;
    }

    public function getRollOptionsSleep(): ?int
    {
        return $this->rollOptionsSleep;
    }

    public function setRollOptionsSleep(?int $rollOptionsSleep): self
    {
        $this->rollOptionsSleep = $rollOptionsSleep;

        return $this;
    }
}
