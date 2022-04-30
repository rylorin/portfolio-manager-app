<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CorporateStatementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CorporateStatementRepository::class)
 */
class CorporateStatement extends Statement
{

  public function getStatementType(): string {
    return Statement::TYPE_CORPORATE;
  }

}
