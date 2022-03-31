<?php

namespace App\Entity;

use App\Repository\OpenOrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OpenOrderRepository::class)
 */
class OpenOrder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $PermId;

    // /**
    //  * @ORM\Column(type="integer", nullable=true)
    //  */
    // private $ClientId;

    // /**
    //  * @ORM\Column(type="integer", nullable=true)
    //  */
    // private $OrderId;

    /**
     * @ORM\ManyToOne(targetEntity=Portfolio::class, inversedBy="openOrders")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Account;

    /**
     * @ORM\ManyToOne(targetEntity=Contract::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $contract;

    /**
     * @ORM\Column(type="string", length=4)
     */
    private $ActionType;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $TotalQty;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $CashQty;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $LmtPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $AuxPrice;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $Status;

    /**
     * @ORM\Column(type="float", nullable=false)
     */
    private $RemainingQty;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPermId(): ?int
    {
        return $this->PermId;
    }

    public function setPermId(int $PermId): self
    {
        $this->PermId = $PermId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

/*     public function getClientId(): ?int
    {
        return $this->ClientId;
    }

    public function setClientId(int $ClientId): self
    {
        $this->ClientId = $ClientId;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->OrderId;
    }

    public function setOrderId(int $OrderId): self
    {
        $this->OrderId = $OrderId;

        return $this;
    }
 */
    public function getAccount(): ?Portfolio
    {
        return $this->Account;
    }

    public function setAccount(?Portfolio $Account): self
    {
        $this->Account = $Account;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): self
    {
        $this->contract = $contract;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getActionType(): ?string
    {
        return $this->ActionType;
    }

    public function setActionType(string $ActionType): self
    {
        $this->ActionType = $ActionType;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getTotalQty(): ?float
    {
        return $this->TotalQty;
    }

    public function setTotalQty(?float $TotalQty): self
    {
        $this->TotalQty = $TotalQty;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCashQty(): ?float
    {
        return $this->CashQty;
    }

    public function setCashQty(?float $CashQty): self
    {
        $this->CashQty = $CashQty;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getLmtPrice(): ?float
    {
        return $this->LmtPrice;
    }

    public function setLmtPrice(?float $LmtPrice): self
    {
        $this->LmtPrice = $LmtPrice;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getAuxPrice(): ?float
    {
        return $this->AuxPrice;
    }

    public function setAuxPrice(?float $AuxPrice): self
    {
        $this->AuxPrice = $AuxPrice;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->Status;
    }

    public function setStatus(string $Status): self
    {
        $this->Status = $Status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getRemainingQty(): ?float
    {
        return $this->RemainingQty;
    }

    public function setRemainingQty(?float $RemainingQty): self
    {
        $this->RemainingQty = $RemainingQty;
        $this->updatedAt = new \DateTime();
        return $this;
    }
    
}
