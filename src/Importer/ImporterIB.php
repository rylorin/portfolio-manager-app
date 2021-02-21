<?php

declare(strict_types=1);

namespace App\Importer;

use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use App\Entity\Contract;
use App\Entity\Stock;
use App\Entity\Option;
use App\Entity\Position;
use App\Entity\Balance;
use App\Entity\Portfolio;
use App\Entity\Currency;
use App\Entity\Statement;
use App\Entity\StockTradeStatement;
use App\Entity\TradeOptionStatement;
use App\Entity\TaxStatement;
use App\Entity\DividendStatement;
use App\Entity\InterestStatement;
use App\Entity\FeeStatement;

class ImporterIB
{

    protected static $exchangeCurrencyMapping = [
        'NYSE' => 'USD', 'NASDAQ' => 'USD', 'AMEX' => 'USD', 'CBOE' => 'USD',
        'SBF' => 'EUR', 'AEB' => 'EUR', 'VSE' => 'EUR', 'BVME' => 'EUR', 'DTB' => 'EUR', 'IBIS2' => 'EUR',
        'LSE' => 'GBP', 'ICEEU' => 'GBP',
        'TSEJ' => 'JPY', 'TSE' => 'CAD' ];
    protected static $monthMapping = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12
    ];
    protected static $monthlyEcheances = [
        'APR20' => 17, 'MAY20' => 15, 'JUN20' => 19, 'JUL20' => 17, 'AUG20' => 21, 'SEP20' => 18, 'DEC20' => 18
    ];
    protected static $symbolMapping = [
        'NGG' => 'NG.',
        'MOH' => 'MC', 'RNL' => 'RNO', 'UBL' => 'URW',
        'BRKB' => 'BRK-B'
    ];
    private static $newFormatCutoff;
    private string $reportType;
    private EntityManagerInterface $em;

    /**
     * constructor.
     *
     * @param EntityManagerInterface $em
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(EntityManagerInterface $em)
    {
//        parent::__construct();
        $this->em = $em;
        self::$newFormatCutoff = new \DateTime('2020-07-31');
    }

    public static function parseOptionSymbol($data): array
    {
        // format: 'NGG AUG20 8.5 P' or 'NG. 18SEP20 8.5 P' or 'BRK B 20DEC19 190.0 P'
        //        $data = 'NG. 18SEP20 8.5 P';
        //        printf("data: '%s'\n", $data);

        $len = strlen($data);
        $type = $data[$len - 1];
        $data = substr($data, 0, $len-2);
        //        printf("remaining data: '%s'\n", $data);

        $len = strlen($data);
        $strikestr = substr(strrchr($data, ' '), 1, $len-1);
        $strike = floatval($strikestr);
        $data = substr($data, 0, $len-strlen($strikestr)-1);
        //        printf("strikestr: '%s', strike '%f', remaining data: '%s'\n", $strikestr, $strike, $data);

        $len = strlen($data);
        $datestr = substr(strrchr($data, ' '), 1, $len-1);
        //        echo($datestr . "\n");
        $date = new \DateTime();
        if ($len > 5)
            $date->setDate(intval(substr($datestr, 5, 2))+2000, self::$monthMapping[substr($datestr, 2, 3)], intval(substr($datestr, 0, 2)));
        else
            $date->setDate(intval(substr($datestr, 3, 2))+2000, self::$monthMapping[substr($datestr, 0, 3)], self::$monthlyEcheances[substr($datestr, 0, 5)]);
        $data = substr($data, 0, $len-strlen($datestr)-1);
        //        printf("remaining data: '%s'\n", $data);

        $symbol = str_replace('.T', '', str_replace(' ', '-', substr($data, 0, strlen($datestr)-1)));

        $result = [ 'symbol' => $symbol, 'date' => $date, 'strike' => $strike, 'type' => $type ];
        return $result;
    }

    public static function parseUSDOptionSymbol($ticker): array
    {
//        print($ticker);
        $date = new \DateTime();    // embarque l'heure et la TZ courantes
        $date->setDate(intval(substr($ticker, 6, 2))+2000, intval(substr($ticker, 8, 2)), intval(substr($ticker, 10, 2)));
        $symbol = trim(substr($ticker, 0, 6));
        if (array_key_exists($symbol, self::$symbolMapping))
            $symbol = self::$symbolMapping[$symbol];
        $strike = intval(substr($ticker, 13, 8)) / 1000;
        $type = $ticker[12];
        $result = [ 'symbol' => $symbol, 'date' => $date, 'strike' => $strike, 'type' => $type ];
        return $result;
    }

    public static function parseEUROptionSymbol($ticker): array
    {
        // P RNL  JUL 20  2000
//        print($ticker);
        $date = new \DateTime();    // embarque l'heure et la TZ courantes
        $date->setDate(intval(substr($ticker, 11, 2))+2000, self::$monthMapping[substr($ticker, 7, 3)], self::$monthlyEcheances[substr($ticker, 7, 3) . substr($ticker, 11, 2)]);
        $symbol = trim(substr($ticker, 2, 4));
        if (array_key_exists($symbol, self::$symbolMapping))
            $symbol = self::$symbolMapping[$symbol];
        $strike = intval(substr($ticker, 14, 5)) / 100;
        $type = $ticker[0];
        return [ 'symbol' => $symbol, 'date' => $date, 'strike' => $strike, 'type' => $type ];
    }

    public static function parseGBPOptionSymbol($ticker): array
    {
        // new format starting on 31/07/2020: NGG AUG20 8.5 P
//        print($ticker);
        $len = strlen($ticker);
        $strikepos = $len - 2;
        while ($ticker[$strikepos-1] != ' ') {
            $strikepos--;
        }
        $date = new \DateTime();
        $date->setDate(intval(substr($ticker, $strikepos-3, 2))+2000, self::$monthMapping[substr($ticker, $strikepos-6, 3)], self::$monthlyEcheances[substr($ticker, $strikepos-6, 5)]);
        $symbol = substr($ticker, 0, $strikepos-7);
        if (array_key_exists($symbol, self::$symbolMapping))
            $symbol = self::$symbolMapping[$symbol];
        $strike = floatval(substr($ticker, $strikepos, $len-$strikepos-2));
        $type = $ticker[$len - 1];
        return [ 'symbol' => $symbol, 'date' => $date, 'strike' => $strike, 'type' => $type ];
    }

    private static function getOperation($codes): string {
        if (in_array('Ex', $codes)) {
            $description = 'Exercise ';
        } elseif (in_array('Ep', $codes)) {
            $description = 'Expired ';
        } elseif (in_array('A', $codes)) {
            $description = 'Assignment ';
        } elseif (in_array('C', $codes)) {
            $description = 'Closing ';
        } elseif (in_array('O', $codes)) {
            $description = 'Opening ';
        } elseif (sizeof($codes) > 0) {
            $description = '';
            printf("\ncode inconnu : '%s'\n", $codes[0]);
        } else {
            $description = '';
        }
        return $description;
    }

    private function findOrCreateStock($s): Stock {
        $symbol = str_replace('.T', '', str_replace(' ', '-', trim($s)));
        $stock = $this->em->getRepository('App:Stock')
          ->findOneBy([ 'symbol' => $symbol ]);
        if (!$stock) {
          $stock = new Stock($symbol);
          $stock->setCurrency('N/A');
          $this->em->persist($stock);
          // flush required therefore futurs lookup will succeed
          $this->em->flush();
        }
        return $stock;
    }

    // Financial Instrument Information,Header,Asset Category,Symbol,Description,Conid,Security ID,Listing Exch,Multiplier,Type,Code
    private function processStockInformation($record): void {
        $stock = $this->findOrCreateStock($record[3]);
        $stock->setName($record[4]);
        $stock->setConId(intval($record[5]));
        $stock->setExchange($record[7]);
        if (array_key_exists($record[7], self::$exchangeCurrencyMapping)) {
            $stock->setCurrency(self::$exchangeCurrencyMapping[$record[7]]);
        }
    }

    private function processStockPosition($portfolio, $record): void {
      print('processStockPosition');
      print_r($record);
      $stock = $this->findOrCreateStock($record[4]);
      $stock->setPrice(floatval($record[8]));
      $stock->setAsk(null);
      $stock->setBid(null);
      $stock->setCurrency($record[3]);
      // update or create position
      $position = $this->em->getRepository('App:Position')->findOneBy(
        [ 'contract' => $stock->getId(), 'portfolio' => $portfolio ]);
      if (!$position) {
      	$position = (new Position())
          ->setPortfolio($portfolio)
          ->setOpenDate((new \DateTime())->sub(new \DateInterval('P1D')))
          ->setContract($stock);
        $this->em->persist($position);
      }
      $position
          ->setQuantity(intval($record[6]))
          ->setCost(floatval($record[7]))
      ;
    }

    // Trades,Data,DataDiscriminator,Asset Category,Currency,Symbol,Date/Time,Quantity,T. Price,Proceeds,Comm/Fee,Basis,Realized P/L,Realized P/L %,Code
    // Trades,Data,DataDiscriminator,Asset Category,Currency,Symbol,Date/Time,Quantity,T. Price,C. Price,Proceeds,Comm/Fee,Basis,Realized P/L,MTM P/L,Code
    // Trades,Data,DataDiscriminator,Asset Category,Currency,Symbol,Date/Time,Quantity,T. Price,C. Price,Proceeds,Comm/Fee,Basis,Realized P/L,Realized P/L %,MTM P/L,Code
    private function processStockTrade($portfolio, $record): void {
        $currency = $record[4];
        $symbol = $record[5];
//        if ($symbol == 'PSEC') print_r($record);
        $date = new \DateTime($record[6]); // "2020-03-02, 09:32:02"
        $quantity = floatval($record[7]);
        $price = floatval($record[8]);
        if (sizeof($record) == 15) {
            $proceeds = floatval($record[9]);
            $fees = floatval($record[10]);
            $pnl = floatval($record[12]);
            $codes = explode(';', $record[14]);
        } elseif (sizeof($record) == 16) {
              $proceeds = floatval($record[10]);
              $fees = floatval($record[11]);
              $pnl = floatval($record[13]);
              $codes = explode(';', $record[15]);
        } elseif (sizeof($record) == 17) {
            $proceeds = floatval($record[10]);
            $fees = floatval($record[11]);
            $pnl = floatval($record[13]);
            $codes = explode(';', $record[16]);
        } else {
            print('error, invalid record');
            print_r($record);
        }
        $amount = $proceeds + $fees;

        $stock = $this->findOrCreateStock($symbol);
        if ($stock->getCurrency() <> $currency) $stock->setCurrency($currency);

        // create trade statement if not exits
        $description = self::getOperation($codes) . $quantity . ' ' . $symbol . '@' . $price . $currency;
        $statement = $this->em->getRepository('App:StockTradeStatement')->findOneBy(
          [ 'stock' => $stock->getId(), 'portfolio' => $portfolio, 'date' => $date ]);
        if (!$statement) {
        	   $statement = (new StockTradeStatement())
                ->setPortfolio($portfolio)
                ->setStock($stock)
                ->setDate($date)
                ;
            if (array_search('A', $codes) >= 0) {
                  $statement->setStatus(Statement::ASSIGNED_STATUS);
            } elseif (array_search('Ep', $codes) >= 0) {
                $statement->setStatus(Statement::EXPIRED_STATUS);
            } elseif (array_search('C', $codes) >= 0) {
                $statement->setStatus(Statement::CLOSE_STATUS);
            } elseif (array_search('O', $codes) >= 0) {
                $statement->setStatus(Statement::OPEN_STATUS);
            } else {
                print_r($codes);
            }
            $this->em->persist($statement);
        } else {
//          print($symbol . ' statement already exists');
        }
        $statement
            ->setCurrency($currency)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setProceeds($proceeds)
            ->setFees($fees)
            ->setAmount($amount)
            ->setRealizedPNL($pnl)
            ->setDescription($description)
            ;
    }

    private function findOrCreateOption($s, $date, $strike, $type): Option {
        $symbol = Option::formatSymbol($s, $date, $strike, $type);
        $contract = $this->em->getRepository('App:Option')->findOneBy(
            [ 'symbol' => $symbol ]);
        if (!$contract) {
            $stock = $this->findOrCreateStock($s);
            $contract = (new Option($symbol))
            ->setStock($stock)
            ->setLastTradeDate($date)
            ->setStrike($strike)
            ->setCallOrPut($type)
            ;
          $this->em->persist($contract);
          // Save this new contract so we can find it later during import
          // moved to main loop            $this->em->flush();
        }
        return $contract;
    }

    private function findOrCreateOption2($s, $date, $strike, $type): Option {
        $stock = $this->findOrCreateStock($s);
        $contract = $this->em->getRepository('App:Option')->findOneBy(
            [ 'stock' => $stock, 'lastTradeDate' => $date, 'strike' => $strike, 'callOrPut' => $type ]);
        if (!$contract) {
            $symbol = Option::formatSymbol($s, $date, $strike, $type);
            $contract = (new Option($symbol))
            ->setStock($stock)
            ->setLastTradeDate($date)
            ->setStrike($strike)
            ->setCallOrPut($type)
            ;
          $this->em->persist($contract);
          // Save this new contract so we can find it later during import
          // moved to main loop            $this->em->flush();
        }
        return $contract;
    }

    // Financial Instrument Information,Data,Asset Category,Symbol,Description,Conid,Listing Exch,Multiplier,Expiry,Delivery Month,Type,Strike,Code
    private function processOptionInformation($record): void {
        // decode description field
        $ticker = $this->parseOptionSymbol($record[4]);
//        $stock = findOrCreateStock($ticker['symbol'], array_key_exists($record[6], self::$exchangeCurrencyMapping) ? self::$exchangeCurrencyMapping[$record[6]] : 'N/A');
        $contract = $this->findOrCreateOption($ticker['symbol'], $ticker['date'], $ticker['strike'], $ticker['type']);
        $contract->setName($record[4]);
        $contract->setConId(intval($record[5]));
        $contract->setExchange($record[6]);
        $contract->setMultiplier(intval(str_replace(',', '', $record[7])));
    }

    // Open Positions,Data,Asset Category,Currency,Symbol,Account,Quantity,Cost Basis,Close Price,Value,Unrealized P/L
    private function processOptionPosition($portfolio, $record): void {
        $currency = $record[3];
        $ticker = $this->parseGenericOptionSymbol($record[4], $currency);
        $option = $this->findOrCreateOption($ticker['symbol'], $ticker['date'], $ticker['strike'], $ticker['type']);
        $option->setCurrency($currency);
        if ($currency == 'GBP') $option->setMultiplier(1000);
        $option->setPrice(floatval($record[8]));
        $position = $this->em->getRepository('App:Position')->findOneBy(
            [ 'contract' => $option->getId(), 'portfolio' => $portfolio ]);
        if (!$position) {
        	$position = (new Position())
            ->setPortfolio($portfolio)
            ->setOpenDate((new \DateTime())->sub(new \DateInterval('P1D')))
            ->setContract($option);
          $this->em->persist($position);
        }
        $position
            ->setQuantity(intval($record[6]))
            ->setCost(floatval($record[7]))
        ;
    }

    private function parseGenericOptionSymbol(string $s, string $currency) {
//        print($s);
//        $t = explode(' ', str_replace('  ', ' ', $s));
//        print_r($t);
        if (true)
            $ticker = $this->parseOptionSymbol($s);
        elseif ($currency == 'USD')
            $ticker = $this->parseUSDOptionSymbol($s);
        elseif ($currency == 'EUR')
            $ticker = $this->parseEUROptionSymbol($s);
        elseif ($currency == 'GBP')
            $ticker = $this->parseGBPOptionSymbol($s);
        else
            $ticker = $this->parseOptionSymbol($s);
//        print_r($ticker);
        return $ticker;
    }

    // Trades,Data,DataDiscriminator,Asset Category,Currency,Symbol,Date/Time,Quantity,T. Price,Proceeds,Comm/Fee,Basis,Realized P/L,Realized P/L %,Code
    private function processOptionTrade($portfolio, $record): void {
        $currency = $record[4];
        $ticker = $this->parseGenericOptionSymbol($record[5], $currency);
        $date = new \DateTime($record[6]); // "2020-03-02, 09:32:02"
        $quantity = floatval($record[7]);
        $price = floatval($record[8]);
        if (sizeof($record) == 15) {
            $proceeds = floatval($record[9]);
            $fees = floatval($record[10]);
            $pnl = floatval($record[12]);
            $codes = explode(';', $record[14]);
        } elseif (sizeof($record) == 16) {
            $proceeds = floatval($record[10]);
            $fees = floatval($record[11]);
            $pnl = floatval($record[13]);
            $codes = explode(';', $record[15]);
        } elseif (sizeof($record) == 17) {
            $proceeds = floatval($record[10]);
            $fees = floatval($record[11]);
            $pnl = floatval($record[13]);
            $codes = explode(';', $record[16]);
        } else {
            print('error, invalid record');
            print_r($record);
        }
        $amount = $proceeds + $fees;

        $option = $this->findOrCreateOption($ticker['symbol'], $ticker['date'], $ticker['strike'], $ticker['type']);
        $option->setCurrency($currency);

        // create trade statement if not exits
        /*
        print_r($codes);
        print($quantity);
        print($option);
        print($price);
        print($currency);
        */
        $description = self::getOperation($codes) . $quantity . ' ' . $option . '@' . $price . $currency;
        $statement = $this->em->getRepository('App:TradeOptionStatement')->findOneBy(
          [ 'contract' => $option->getId(), 'portfolio' => $portfolio, 'date' => $date ]);
        if (!$statement) {
            $stock = $this->findOrCreateStock($ticker['symbol']);
            if ($stock->getCurrency() <> $currency) $stock->setCurrency($currency);
        	  $statement = (new TradeOptionStatement())
                ->setPortfolio($portfolio)
                ->setDate($date)
                ->setStock($stock)
                ->setContract($option)
                ;
            if (array_search('A', $codes) >= 0) {
                  $statement->setStatus(Statement::ASSIGNED_STATUS);
            } elseif (array_search('Ep', $codes) >= 0) {
                $statement->setStatus(Statement::EXPIRED_STATUS);
            } elseif (array_search('C', $codes) >= 0) {
                $statement->setStatus(Statement::CLOSE_STATUS);
            } elseif (array_search('O', $codes) >= 0) {
                $statement->setStatus(Statement::OPEN_STATUS);
            } else {
                print_r($codes);
            }
            $this->em->persist($statement);
        }
        $statement
            ->setCurrency($currency)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setProceeds($proceeds)
            ->setFees($fees)
            ->setAmount($amount)
            ->setRealizedPNL($pnl)
            ->setDescription($description)
            ;
    }

    // Dividends,Data,Currency,Date,Description,Amount
    private function processDividend($portfolio, $record): void {
        if (!$record[4]) return;
        $currency = $record[2];
        $date = new \DateTime($record[3]);
        $pos = strpos($record[4], '(');
        $symbol = str_replace('.T', '', str_replace(' ', '-', trim(substr($record[4], 0, $pos))));
        $country = substr($record[4], $pos + 1, 2);
        $amount = floatval($record[5]);
        $description = $record[4];

        $stock = $this->findOrCreateStock($symbol);
        if ($stock->getCurrency() <> $currency) $stock->setCurrency($currency);

        // create statement if not exits
        $statement = $this->em->getRepository('App:DividendStatement')->findOneBy(
          [ 'stock' => $stock->getId(), 'portfolio' => $portfolio, 'date' => $date, 'description' => $description ]);
        if (!$statement) {
        	$statement = (new DividendStatement())
                ->setPortfolio($portfolio)
                ->setStock($stock)
                ->setDate($date)
                ->setDescription($description)
                ;
            $this->em->persist($statement);
        }
        $statement
            ->setCurrency($currency)
            ->setCountry($country)
            ->setAmount($amount)
            ;
        /* debug
        if ($stock->getSymbol() == 'HYT') {
            printf("\n Dividend %d\n amount = %f\n", $statement->getId(), $amount);
        }
        */
    }

    // Withholding Tax,Header,Currency,Date,Description,Amount,Code
    private function processTax($portfolio, $record): void {
        if (!$record[4]) return;
        $currency = $record[2];
        $date = new \DateTime($record[3]);
        $pos = strpos($record[4], '(');
        $symbol = str_replace('.T', '', str_replace(' ', '-', trim(substr($record[4], 0, $pos))));
        $country = substr($record[4], $pos + 1, 2);
        $amount = floatval($record[5]);
        $description = $record[4];

        $stock = $this->findOrCreateStock($symbol);
        if ($stock->getCurrency() <> $currency) $stock->setCurrency($currency);

        // create statement if not exits
        $statement = $this->em->getRepository('App:TaxStatement')->findOneBy(
          [ 'portfolio' => $portfolio, 'stock' => $stock->getId(), 'date' => $date, 'amount' => $amount, 'description' => $description ]);
        if (!$statement) {
        	$statement = (new TaxStatement())
                ->setPortfolio($portfolio)
                ->setStock($stock)
                ->setDate($date)
                ->setDescription($description)
                ->setAmount($amount)
                ;
            $this->em->persist($statement);
        }
        $statement
            ->setCurrency($currency)
            ->setCountry($country)
            ;
        /* debug
        if ($stock->getSymbol() == 'HYT') {
            printf("\n Tax %d\n amount = %f\n", $statement->getId(), $amount);
        }
        */
    }

    // Interest,Header,Currency,Date,Description,Amount
    private function processInterest($portfolio, $record): void {
        if (!$record[4]) return;
        $currency = $record[2];
        $date = new \DateTime($record[3]);
        $amount = floatval($record[5]);
        $description = $record[4];

        // create statement if not exits
        $statement = $this->em->getRepository('App:InterestStatement')->findOneBy(
          [ 'portfolio' => $portfolio, 'date' => $date, 'amount' => $amount, 'description' => $description ]);
        if (!$statement) {
        	$statement = (new InterestStatement())
                ->setPortfolio($portfolio)
                ->setDate($date)
                ->setDescription($description)
                ->setAmount($amount)
                ;
            $this->em->persist($statement);
        }
        $statement
            ->setCurrency($currency)
            ;
    }

    // Transaction Fees,Header,Asset Category,Currency,Date/Time,Symbol,Description,Quantity,Trade Price,Amount,Code
    private function processTransactionFee($portfolio, $record): void {
        $currency = $record[3];
        $date = new \DateTime($record[4]);
        $symbol = str_replace('.T', '', str_replace(' ', '-', trim($record[5])));
        $amount = floatval($record[9]);
        $description = $record[6];

        $stock = $this->findOrCreateStock($symbol);
        if ($stock->getCurrency() <> $currency) $stock->setCurrency($currency);

        // create statement if not exits
        $statement = $this->em->getRepository('App:FeeStatement')->findOneBy(
          [ 'portfolio' => $portfolio, 'stock' => $stock->getId(), 'date' => $date, 'amount' => $amount, 'description' => $description ]);
        if (!$statement) {
        	$statement = (new FeeStatement())
                ->setPortfolio($portfolio)
                ->setStock($stock)
                ->setDate($date)
                ->setDescription($description)
                ->setAmount($amount)
                ->setCurrency($currency)
                ;
            $this->em->persist($statement);
        }
    }

    private function processForexBalance($portfolio, $record): void {
        // update or create stock
        $cash = $this->em->getRepository('App:Balance')->findOneBy([ 'currency' => $record[4], 'portfolio' => $portfolio ]);
        if (!$cash) {
            $cash = (new Balance())
            	->setPortfolio($portfolio)
            	->setCurrency($record[4]);
            $this->em->persist($cash);
        }
        $cash->setQuantity(floatval($record[5]));
    }

    private function processAccountNumber($record): Portfolio {
        $portfolio = $this->em->getRepository('App:Portfolio')->findOneBy([ 'account' => $record[3] ]);
        if (!$portfolio) {
            $portfolio = new Portfolio();
            $portfolio->setAccount($record[3]);
            $portfolio->setBaseCurrency('N/A');
            $this->em->persist($portfolio);
        }
        return $portfolio;
    }

    /*
     * Account Information,Header,Name,Account,Base Currency
     */
    private function processAccountInformation($record): Portfolio {
        $portfolio = $this->em->getRepository('App:Portfolio')->findOneBy([ 'account' => $record[3] ]);
        if (!$portfolio) {
            $portfolio = new Portfolio();
            $portfolio->setAccount($record[3]);
            $this->em->persist($portfolio);
        } else {
          // reset all quantities to zero as we will load every existing positions
          foreach ( $portfolio->getPositions() as $position ) {
            $position->setQuantity(0);
          }
        }
        $portfolio->setBaseCurrency($record[4]);
        return $portfolio;
    }

    private function processAccountCurrency($portfolio, $record): void {
        $portfolio->setBaseCurrency($record[3]);
    }

    private function processCurrencyRate($portfolio, $record): void {
    	$base = $portfolio->getBaseCurrency();
        $currency = $this->em->getRepository('App:Currency')->findOneBy(
                [ 'base' => $base, 'currency' => $record[2] ]
                );
        if (!$currency) {
            $currency = new Currency();
            $currency->setBase($base);
            $currency->setCurrency($record[2]);
            $this->em->persist($currency);
        }
        $currency->setRate(1.0 / floatval($record[3]));
        // invert currency
        $currency = $this->em->getRepository('App:Currency')->findOneBy(
        		[ 'base' => $record[2], 'currency' => $base ]
        		);
        if (!$currency) {
        	$currency = new Currency();
        	$currency->setBase($record[2]);
        	$currency->setCurrency($base);
        	$this->em->persist($currency);
        }
        $currency->setRate(floatval($record[3]));
    }

    public function start(string $fileName) {
        $this->reportType = 'unknown';

        $reader = Reader::createFromPath($fileName);
        $records = $reader->getRecords();

        return $records;
    }

    public function processOneRecord(?Portfolio $portfolio, $record): ?Portfolio {
        // portfolio related lines
        if ($record[0] == 'Open Positions' && $record[1] == 'Data' && $record[2] == 'Stocks' && $record[5]) {
            $this->processStockPosition($portfolio, $record);
        }
        elseif ($record[0] == 'Open Positions' && $record[1] == 'Data' && $record[2] == 'Equity and Index Options' && $record[5]) {
            $this->processOptionPosition($portfolio, $record);
        }
        elseif ($record[0] == 'Forex Balances' && $record[1] == 'Data' && substr($record[2], 0, 5) == 'Forex') {
            $this->processForexBalance($portfolio, $record);
        }
        elseif ($record[0] == 'Base Currency Exchange Rate' && $record[1] == 'Data') {
            $this->processCurrencyRate($portfolio, $record);
        }
        elseif ($record[0] == 'Trades' && $record[1] == 'Data' && $record[2] == 'Order' && substr($record[3], 0, 6) == 'Stocks') {
            $this->processStockTrade($portfolio, $record);
        }
        elseif ($record[0] == 'Trades' && $record[1] == 'Data' && $record[2] == 'Order' && substr($record[3], 0, 24) == 'Equity and Index Options') {
            $this->processOptionTrade($portfolio, $record);
        }
        elseif ($record[0] == 'Dividends' && $record[1] == 'Data') {
            $this->processDividend($portfolio, $record);
        }
        elseif ($record[0] == 'Withholding Tax' && $record[1] == 'Data') {
            $this->processTax($portfolio, $record);
        }
        elseif ($record[0] == 'Interest' && $record[1] == 'Data') {
            $this->processInterest($portfolio, $record);
        }
        elseif ($record[0] == 'Transaction Fees' && $record[1] == 'Data' && substr($record[2], 0, 6) == 'Stocks') {
            $this->processTransactionFee($portfolio, $record);
        }
        // portfolio unrelated lines
        elseif ($record[0] == 'Financial Instrument Information' && $record[1] == 'Data' && $record[2] == 'Stocks') {
            $this->processStockInformation($record);
        }
        elseif ($record[0] == 'Financial Instrument Information' && $record[1] == 'Data' && $record[2] == 'Equity and Index Options') {
            $this->processOptionInformation($record);
        }
        // Account information lines, get account number and base currency to obtain portfolio
        elseif ($record[0] == 'Account Information' && $record[1] == 'Data' && sizeof($record) == 5) {
            $portfolio = $this->processAccountInformation($record);
        }
        elseif ($record[0] == 'Account Information' && $record[1] == 'Data' && $record[2] == 'Account') {
            $portfolio = $this->processAccountNumber($record);
        }
        elseif ($record[0] == 'Account Information' && $record[1] == 'Data' && $record[2] == 'Base Currency' && $portfolio) {
            $this->processAccountCurrency($portfolio, $record);
        }
        // First line to process, for information only
        elseif ($record[0] == 'Statement' && $record[1] == 'Data' && $record[2] == 'Title') {
            $this->reportType = $record[3];
        }
        elseif ($record[0] == 'Statement' && $record[1] == 'Data' && $record[2] == 'WhenGenerated') {
            $this->whenGenerated = new \DateTime($record[3]);
        }
        else {
//            	printf("ignored: %s,%s,%s\n", $record[0], $record[1], $record[2]);
        }
        // save / write the changes to the database
        $this->em->flush();
        return $portfolio;
    }

    public function getReportType(): string {
        return $this->reportType;
    }

}
