<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\StatementRepository;

/**
 * @ORM\Entity(repositoryClass=StatementRepository::class)
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="statement_type", type="string", length=4)
 * @ORM\DiscriminatorMap({
 *     "Trade"="StockTradeStatement",
 *     "TradeOption"="OptionTradeStatement",
 *     "Dividend"="DividendStatement",
 *     "Tax"="TaxStatement",
 *     "Interest"="InterestStatement",
 *     "OtherFee"="FeeStatement",
 *     "CorporateStatement"="CorporateStatement",
 *     "Cash"="CashStatement",
 * })
 */
abstract class Statement
{
    const TYPE_TRADE = 'Trade';
    const TYPE_TRADE_OPTION = 'TradeOption';
    const TYPE_DIVIDEND = 'Dividend';
    const TYPE_TAX = 'Tax';
    const TYPE_INTEREST = 'Interest';
    const TYPE_FEE = 'OtherFee';
    const TYPE_CORPORATE = 'CorporateStatement';
    const TYPE_CASH = 'Cash';

    // trades statuses
    public const OPEN_STATUS = 1;
    public const CLOSE_STATUS = 2;
    public const EXPIRED_STATUS = 3;
    public const ASSIGNED_STATUS = 4;
    public const EXERCISED_STATUS = 5;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=3)
     */
    private $currency;

    /**
     * @ORM\Column(type="float", nullable=false)
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="integer", nullable=true, name="transaction_id")
     */
    private $transactionID;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fxRateToBase;

    /**
     * @ORM\ManyToOne(targetEntity=Portfolio::class, inversedBy="statements")
     * @ORM\JoinColumn(nullable=false)
     */
    private $portfolio;

    /**
     * @ORM\ManyToOne(targetEntity=TradeUnit::class, inversedBy="openingTrades")
     */
    private $tradeUnit;

    /**
     * Not really correct regarding the model but useful to make the "Performance Report by Stock" easier
     *
     * @ORM\ManyToOne(targetEntity=Contract::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $stock;

    public function getId(): ?int
    {
        return $this->id;
    }

    abstract public function getStatementType(): string;

    abstract public function getRealizedPNL(): ?float;

    abstract public function getFees(): ?float;

    public function getQuantity(): ?float
    {
        return null;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getStock(): ?Contract
    {
        return $this->stock;
    }

    public function setStock(?Contract $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $netAmount): self
    {
        $this->amount = $netAmount;
        return $this;
    }

    public function getTradeUnit(): ?TradeUnit
    {
        return $this->tradeUnit;
    }

    public function setTradeUnit(?TradeUnit $tradeUnit): self
    {
        if ($this->tradeUnit && ($this->tradeUnit != $tradeUnit)) {
            $this->tradeUnit->removeOpeningTrade($this);
        }
        $this->tradeUnit = $tradeUnit;
        if ($this->tradeUnit) {
            $this->tradeUnit->addOpeningTrade($this);
        }
        return $this;
    }

    public function getTransactionId(): ?int
    {
        return $this->transactionID;
    }

    public function setTransactionId(?int $tradeId): self
    {
        $this->transactionID = $tradeId;
        return $this;
    }

    public function getFxRateToBase(): ?float
    {
        return $this->fxRateToBase;
    }

    public function setFxRateToBase(?float $fxRateToBase): self
    {
        $this->fxRateToBase = $fxRateToBase;

        return $this;
    }

    public function getContract(): ?Contract
    {
        return null;
    }

}