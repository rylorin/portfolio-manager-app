<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class CashContract extends Contract {

    public function getSecType(): string {
        return Contract::TYPE_CASH;
      }  
  
      public function getYahooTicker(): string {
          return $this->symbol;
      }
  
    }
