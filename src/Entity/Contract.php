<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ContractRepository;

/**
 * @ORM\Entity(repositoryClass=ContractRepository::class)
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="secType", type="string", length=4)
 * @ORM\DiscriminatorMap({
 *     "STK"="Stock",
 *     "OPT"="Option",
 *     "CASH"="CashContract",
 *     "BAG"="BagContract"
 * })
 **/
abstract class Contract
{
    public const TYPE_STOCK = 'STK';
    public const TYPE_OPTION = 'OPT';
    public const TYPE_CASH = 'CASH';
    public const TYPE_BAG = 'BAG';
    public const EXCHANGES = [
        'NYSE' => 'NYSE', 'NASDAQ' => 'NASDAQ', 'ARCA' => 'ARCA', 'IBIS2' => 'IBIS2', 'AMEX' => 'AMEX', 'CBOE' => 'CBOE',
        'SBF' => 'SBF', 'AEB' => 'AEB', 'VSE' => 'VSE', 'BVME' => 'BVME', 'DTB' => 'DTB', 'IBIS' => 'IBIS',
        'LSE' => 'LSE', 'ICEEU' => 'ICEEU',
        'TSEJ' => 'TSEJ', 'TSE' => 'TSE',
        'EBS' => 'EBS'
    ];

    // Trading View mappings
    protected const marketPlaceMapping = [
      'SBF' => 'EURONEXT', 'AEB' => 'EURONEXT', 'IBIS2' => 'EURONEXT', 'IBIS' => 'XETR',
      'TSEJ' => 'TSE', 'TSE' => 'NEO', 'EBS' => 'SIX',
      'ARCA' => 'AMEX', 'PINK' => 'OTC'
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="createdAt")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true, name="updatedAt")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $conId;

    /**
     * @ORM\Column(type="string", length=19, nullable=false, unique=true)
     */
    private $symbol;

    /**
     * @ORM\Column(type="string", length=3, nullable=false)
     */
    private $currency;

    /**
     * @ORM\OneToMany(targetEntity=Position::class, mappedBy="contract", orphanRemoval=true)
     */
    private $positions;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $exchange;

    /**
     * @ORM\Column(type="string", length=31, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fiftyTwoWeekLow;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fiftyTwoWeekHigh;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $bid;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ask;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $previousClosePrice;

    // /**
    //  * @ORM\Column(type="integer", nullable=true)
    //  */
    // private $ApiReqId;

    // /**
    //  * @ORM\Column(type="datetime", nullable=true)
    //  */
    // private $askDate;

    // /**
    //  * @ORM\Column(type="datetime", nullable=true)
    //  */
    // private $bidDate;

    // /**
    //  * @ORM\Column(type="float", nullable=true)
    //  */
    // private $tickPrice;

    public function __construct(?string $symbol = null)
    {
        $this->positions = new ArrayCollection();
        $this->symbol = $symbol;
        $this->createdAt = new \DateTime();
    }

    public function __toString()
    {
        return $this->symbol;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    abstract public function getSecType(): string;

    public function getConId(): ?int
    {
        return $this->conId;
    }

    public function setConId(?int $contractIB): self
    {
        $this->conId = $contractIB;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(?string $symbol): self
    {
        $this->symbol = $symbol;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        $this->updated = new \DateTime();
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
            $position->setContract($this);
        }

        return $this;
    }

    public function removePosition(Position $position): self
    {
        if ($this->positions->contains($position)) {
            $this->positions->removeElement($position);
            // set the owning side to null (unless already changed)
            if ($position->getContract() === $this) {
                $position->setContract(null);
            }
        }

        return $this;
    }

    public function getExchange(): ?string
    {
        return $this->exchange;
    }

    public function setExchange(?string $exchange): self
    {
        $this->exchange = $exchange;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getMarketPlace(): ?string
    {
        return $this->exchange ?
          (array_key_exists($this->exchange, self::marketPlaceMapping) ? self::marketPlaceMapping[$this->exchange] : $this->exchange) :
          null;
    }

    abstract public function getYahooTicker(): ?string;

    public function getChangePercent(): ?float
    {
      return $this->previousClosePrice ? (($this->getPrice() / $this->previousClosePrice) - 1) : null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getFiftyTwoWeekLow(): ?float
    {
        return $this->fiftyTwoWeekLow;
    }

    public function setFiftyTwoWeekLow(?float $fiftyTwoWeekLow): self
    {
        $this->fiftyTwoWeekLow = $fiftyTwoWeekLow;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getFiftyTwoWeekHigh(): ?float
    {
        return $this->fiftyTwoWeekHigh;
    }

    public function setFiftyTwoWeekHigh(?float $fiftyTwoWeekHigh): self
    {
        $this->fiftyTwoWeekHigh = $fiftyTwoWeekHigh;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getPrice(): ?float
    {
        $result = null;
        if ($this->ask && $this->bid) $result = ($this->ask + $this->bid) / 2;
        elseif ($this->price) $result = $this->price;
        else $result = $this->previousClosePrice;
        return $result;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getBid(): ?float
    {
        return $this->bid;
    }

    public function setBid(?float $bid): self
    {
        $this->bid = $bid;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getAsk(): ?float
    {
        return $this->ask;
    }

    public function setAsk(?float $ask): self
    {
        $this->ask = $ask;
        $this->updated = new \DateTime();
        return $this;
    }

    public function getPreviousClosePrice(): ?float
    {
        return $this->previousClosePrice;
    }

    public function setPreviousClosePrice(?float $previousClosePrice): self
    {
        $this->previousClosePrice = $previousClosePrice;
        $this->updated = new \DateTime();
        return $this;
    }

    public static function normalizeSymbol(string $symbol): string {
        // print_r($symbol);
        $symbol = str_replace('.T', '', str_replace(' ', '-', trim($symbol)));
        if ($symbol[strlen($symbol)-1] == 'd') {
            $symbol = substr($symbol, 0, -1);
        }
        return $symbol;
    }

    // public function getApiReqId(): ?int
    // {
    //     return $this->ApiReqId;
    // }

    // public function getAskDate(): ?\DateTimeInterface
    // {
    //     return $this->askDate;
    // }

    // public function setAskDate(?\DateTimeInterface $askDate): self
    // {
    //     $this->askDate = $askDate;

    //     return $this;
    // }

    // public function getBidDate(): ?\DateTimeInterface
    // {
    //     return $this->bidDate;
    // }

    // public function setBidDate(?\DateTimeInterface $bidDate): self
    // {
    //     $this->bidDate = $bidDate;

    //     return $this;
    // }

    // public function getTickPrice(): ?float
    // {
    //     return $this->tickPrice;
    // }

    // public function setTickPrice(?float $tickPrice): self
    // {
    //     $this->tickPrice = $tickPrice;
    //     $this->updated = new \DateTime();
    //     return $this;
    // }

    public function getMultiplier(): int {
        return 1;
    }
  
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
  
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
  
}
