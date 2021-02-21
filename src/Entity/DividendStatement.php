<?php

namespace App\Entity;

use App\Repository\DividendStatementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DividendStatementRepository::class)
 * @ORM\Table(name="dividend")
 */
class DividendStatement extends Statement
{

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $country;

    public function getStatementType(): string {
      return Statement::TYPE_DIVIDEND;
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
