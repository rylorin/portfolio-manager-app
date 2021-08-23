<?php

declare(strict_types=1);

namespace rylorin\FmtFinanceApi\Results;

class Quote implements \JsonSerializable
{
    private $symbol;
    private $price;
    private $changesPercentage;
    private $change;
    private $dayLow;
    private $dayHigh;
    private $yearHigh;
    private $yearLow;
    private $marketCap;
    private $priceAvg50;
    private $priceAvg200;
//    private $volume;
  //  private $avgVolume;
    //private $exchange;
    
    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        foreach ($values as $property => $value) {
            $this->{$property} = $value;
        }
    }
    
    public function __call($name, $arguments)
    {
    	if (substr($name, 0, 3) == 'get') {
    		return($this->{lcfirst(substr($name, 3))});
    	}
    }
    
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}
