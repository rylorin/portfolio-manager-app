<?php

namespace App\Entity;

use App\Repository\TradeOptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TradeOptionRepository::class)
 * @ORM\Table(name="trade_option")
 */
class OptionTradeStatement extends Statement
{
    // statuses
    public const OPEN_STATUS = 1;
    public const CLOSE_STATUS = 2;
    public const EXPIRED_STATUS = 3;
    public const ASSIGNED_STATUS = 4;

    /**
     * @ORM\ManyToOne(targetEntity=Contract::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $contract;

    /**
     * @ORM\Column(type="float")
     */
    private $quantity;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $proceeds;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fees;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $realizedPNL;

    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    private $status;

    public function getStatementType(): string {
      return Statement::TYPE_TRADE_OPTION;
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

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getProceeds(): ?float
    {
        return $this->proceeds;
    }

    public function setProceeds(?float $proceeds): self
    {
        $this->proceeds = $proceeds;
        return $this;
    }

    public function getFees(): ?float
    {
        return $this->fees;
    }

    public function setFees(?float $fees): self
    {
        $this->fees = $fees;
        return $this;
    }

    public function getRealizedPNL(): ?float
    {
        return $this->realizedPNL;
    }

    public function setRealizedPNL(?float $realizedPNL): self
    {
        $this->realizedPNL = $realizedPNL;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

}
