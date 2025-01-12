<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class BagContract extends Contract {

    public function getSecType(): string {
        return Contract::TYPE_BAG;
    }  
  
    public function getYahooTicker(): ?string {
        return null;
    }
  
}
