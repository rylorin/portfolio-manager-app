<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class IndexContract extends Contract {

    public function getSecType(): string {
        return Contract::TYPE_IND;
    }  
  
    public function getYahooTicker(): ?string {
        return null;
    }
  
}
