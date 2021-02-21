<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InterestStatementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InterestStatementRepository::class)
 */
class InterestStatement extends Statement
{

    public function getStatementType(): string {
      return Statement::TYPE_INTEREST;
    }

}
