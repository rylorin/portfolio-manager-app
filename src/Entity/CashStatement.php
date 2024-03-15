<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StatementRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StatementRepository::class)
 */
class CashStatement extends Statement
{

    public function getStatementType(): string
    {
        return Statement::TYPE_CASH;
    }

    public function getRealizedPNL(): ?float
    {
        return 0;
    }

    public function getFees(): ?float
    {
        return 0;
    }
}