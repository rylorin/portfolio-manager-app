<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FeeStatementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FeeStatementRepository::class)
 */
class FeeStatement extends Statement
{

  public function getStatementType(): string {
    return Statement::TYPE_FEE;
  }

  public function getRealizedPNL(): ?float
  {
      return 0;
  }

  public function getFees(): ?float
  {
      return $this->getAmount();
  }

}
