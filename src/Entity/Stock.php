<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StockRepository::class)
 */
class Stock extends Contract
{
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $dividendTTM;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $EpsTTM;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $EpsForward;

    /**
     * @ORM\Column(type="string", length=31, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $industry;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $subcategory;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $HistoricalVolatility;

    public function __construct($symbol = null)
    {
        parent::__construct($symbol);
        $this->options = new ArrayCollection();
        $this->tradeUnits = new ArrayCollection();
    }

    public function getSecType(): string {
      return Contract::TYPE_STOCK;
    }

    /**
     * @return Collection|Option[]
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(Option $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options[] = $option;
            $option->setStock($this);
        }

        return $this;
    }

    public function removeOption(Option $option): self
    {
        if ($this->options->contains($option)) {
            $this->options->removeElement($option);
            // set the owning side to null (unless already changed)
            if ($option->getStock() === $this) {
                $option->setStock(null);
            }
        }

        return $this;
    }

    public function getYahooTicker(): ?string
    {
        $ticker = str_replace('-PR', '-P', $this->getSymbol());
        $exchange = $this->getExchange();
        if (($exchange == 'SBF') || ($exchange == 'IBIS2')) {
            $ticker = $ticker . '.PA';
        } elseif (($exchange == 'LSE') || ($exchange == 'LSEETF')) {
            $ticker = $ticker . '.L';
        } elseif ($exchange == 'VSE') {
            $ticker = $ticker . '.VI';
        } elseif ($exchange == 'BVME') {
            $ticker = $ticker . '.MI';
        } elseif ($exchange == 'TSEJ') {
            $ticker = $ticker . '.T';
        } elseif (($exchange == 'TSE') || ($this->getCurrency() == 'CAD')) {
            $ticker = str_replace('.', '-', $ticker) . '.TO';
        } elseif ($exchange == 'AEB') {
            $ticker = $ticker . '.AS';
        } elseif ($exchange == 'EBS') {
            $ticker = $ticker . '.SW';
        } elseif ($exchange == 'FWB') {
            $ticker = $ticker . '.DE';
        } elseif ($exchange == 'IBIS') {
            $ticker = (($ticker[strlen($ticker)-1] == 'd') ? substr($ticker, 0, strlen($ticker)-1) : $ticker) . '.DE';
        }
        return $ticker;
    }

    public function getDividendTTM(): ?float
    {
        return $this->dividendTTM;
    }

    public function setDividendTTM(?float $dividendTTM): self
    {
        $this->dividendTTM = $dividendTTM;

        return $this;
    }

    public function getDividendYield(): ?float
    {
        return $this->getPrice() ? $this->dividendTTM / $this->getPrice() : null;
    }

    public function getEpsTTM(): ?float
    {
        return $this->EpsTTM;
    }

    public function setEpsTTM(?float $EpsTTM): self
    {
        $this->EpsTTM = $EpsTTM;

        return $this;
    }

    public function getEpsForward(): ?float
    {
        return $this->EpsForward;
    }

    public function setEpsForward(?float $EpsForward): self
    {
        $this->EpsForward = $EpsForward;

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
            $tradeUnit->setSymbol($this);
        }

        return $this;
    }

    public function removeTradeUnit(TradeUnit $tradeUnit): self
    {
        if ($this->tradeUnits->removeElement($tradeUnit)) {
            // set the owning side to null (unless already changed)
            if ($tradeUnit->getSymbol() === $this) {
                $tradeUnit->setSymbol(null);
            }
        }

        return $this;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function setIndustry(string $industry): self
    {
        $this->industry = $industry;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSubcategory(): ?string
    {
        return $this->subcategory;
    }

    public function setSubcategory(?string $subcategory): self
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    public function getHistoricalVolatility(): ?float
    {
        return $this->HistoricalVolatility;
    }

    public function setHistoricalVolatility(?float $HistoricalVolatility): self
    {
        $this->HistoricalVolatility = $HistoricalVolatility;

        return $this;
    }

}
