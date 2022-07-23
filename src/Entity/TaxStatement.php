<?php

namespace App\Entity;

use App\Repository\TaxRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TaxRepository::class)
 * @ORM\Table(name="tax")
 */
class TaxStatement extends Statement
{

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $country;

    public function getStatementType(): string {
      return Statement::TYPE_TAX;
    }

    public function getRealizedPNL(): ?float
    {
        return $this->getAmount();
    }
  
    public function getFees(): ?float
    {
        return 0;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

}
