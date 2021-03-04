<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\User\User;
use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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

    public function __construct()
    {
        $this->positions = new ArrayCollection();
        $this->balances = new ArrayCollection();
        $this->statements = new ArrayCollection();
        $this->tradeUnits = new ArrayCollection();
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
}
