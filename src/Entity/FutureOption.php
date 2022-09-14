<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OptionRepository;
use App\Entity\Contract;
use App\Entity\Option;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OptionRepository::class)
 * @ORM\Table(name="option_alias")
 */
class FutureOption extends Option
{

    public function __construct()
    {
        parent::__construct();
        $this->setMultiplier(50);
        $this->createdAt = new \DateTime();
    }

    public function getSecType(): string {
      return Contract::TYPE_FOP;
    }

    // public function getUpdatedAt(): ?\DateTimeInterface
    // {
    //     return $this->updatedAt;
    // }
  
    // private function setUpdatedAt(?\DateTimeInterface $updated): self
    // {
    //     $this->updatedAt = $updated;
    //     return $this;
    // }

}
