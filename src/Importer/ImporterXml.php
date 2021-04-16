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
use App\Entity\OptionTradeStatement;
use App\Entity\TaxStatement;
use App\Entity\DividendStatement;
use App\Entity\InterestStatement;
use App\Entity\FeeStatement;

class ImporterXml
{
  private EntityManagerInterface $em;
  private $portfolios = array();

  /**
   * constructor.
   *
   * @param EntityManagerInterface $em
   *
   * @throws \Symfony\Component\Console\Exception\LogicException
   */
  public function __construct(EntityManagerInterface $em)
  {
      $this->em = $em;
  }

  public function findOrCreatePortfolio(string $account): Portfolio {
    if (!array_key_exists($account, $this->portfolios)) {
      $portfolio = $this->em->getRepository('App:Portfolio')->findOneBy([ 'account' => $account ]);
      if (!$portfolio) {
          $portfolio = new Portfolio();
          $portfolio->setAccount($account);
          $portfolio->setBaseCurrency('N/A');
          $this->em->persist($portfolio);
      }
      $this->portfolios[$account] = $portfolio;
    }
    return $this->portfolios[$account];
  }

  private function findOrCreateStock(\SimpleXMLElement $xml): Stock {
//    print((string)$xml->attributes()->symbol);
    $stock = $this->em->getRepository('App:Stock')
      ->findOneBy([ 'conId' => (string)$xml->attributes()->conid ]);
    if (!$stock) {
      $symbol = str_replace('.T', '', str_replace(' ', '-', trim((string)$xml->attributes()->symbol)));
      if ($symbol[sizeof($symbol)-1] == 'd') array_pop($symbol);
      $stock = $this->em->getRepository('App:Stock')
        ->findOneBy([ 'symbol' => $symbol ]);
      if (!$stock) {
        $stock = new Stock($symbol);
        $this->em->persist($stock);
      }
      $stock->setConId((int)$xml->attributes()->conid);
      // flush required therefore futurs lookup will succeed
      $this->em->flush();
    }
    if ($xml->attributes()->listingExchange && ((string)$xml->attributes()->listingExchange != $stock->getExchange()))
      $stock->setExchange((string)$xml->attributes()->listingExchange);
    if ($xml->attributes()->currency && ((string)$xml->attributes()->currency != $stock->getCurrency()))
      $stock->setCurrency((string)$xml->attributes()->currency);
    if ($xml->attributes()->description && !$stock->getName())
      $stock->setName((string)$xml->attributes()->description);
    return $stock;
  }

  private function findOrCreateOption(\SimpleXMLElement $xml): Option {
//    print((string)$xml->attributes()->symbol);
    $contract = $this->em->getRepository('App:Option')
      ->findOneBy([ 'conId' => (string)$xml->attributes()->conid ]);
    if (!$contract) {
      $stock = $this->em->getRepository('App:Stock')
        ->findOneBy([ 'conId' => (string)$xml->attributes()->underlyingConid ]);
      if (!$stock) {
        $symbol = str_replace('.T', '', str_replace(' ', '-', trim((string)$xml->attributes()->underlyingSymbol)));
        if ($symbol[sizeof($symbol)-1] == 'd') array_pop($symbol);
        $stock = $this->em->getRepository('App:Stock')
          ->findOneBy([ 'symbol' => $symbol ]);
        if (!$stock) {
          $stock = new Stock($symbol);
          $stock->setExchange((string)$xml->attributes()->underlyingListingExchange);
          $stock->setCurrency((string)$xml->attributes()->currency);
          $this->em->persist($stock);
        }
        $stock->setConId((int)$xml->attributes()->underlyingConid);
      }

      $contract = $this->em->getRepository('App:Option')
        ->findOneBy([
          'stock' => $stock,
          'lastTradeDate' => new \DateTime((string)$xml->attributes()->expiry),
          'strike' => (float)$xml->attributes()->strike,
          'callOrPut' => (string)$xml->attributes()->putCall
         ]);
      if (!$contract) {
          // option contract does not exist
          $contract = (new Option((string)$xml->attributes()->symbol))
            ->setCurrency((string)$xml->attributes()->currency)
            ->setStock($stock)
            ->setLastTradeDate(new \DateTime((string)$xml->attributes()->expiry))
            ->setStrike((float)$xml->attributes()->strike)
            ->setCallOrPut((string)$xml->attributes()->putCall)
            ->setName((string)$xml->attributes()->description)
            ->setMultiplier((int)$xml->attributes()->multiplier)
            ;

        $this->em->persist($contract);
        // flush required therefore futurs lookup will succeed
        $this->em->flush();
      }
    }
    return $contract;
  }

  private function processStockTrade(Portfolio $portfolio, \SimpleXMLElement $xml): void {
    $tradeId = intval((string)$xml->attributes()->transactionID);
//print("tradeId: " . $tradeId . "\n");
    $statement = $this->em->getRepository('App:StockTradeStatement')->findOneBy(
      [ 'portfolio' => $portfolio, 'tradeId' => $tradeId ]);
    if (!$statement) {
      $currency = (string)$xml->attributes()->currency;
      $symbol = (string)$xml->attributes()->symbol;
      $date = new \DateTime((string)$xml->attributes()->dateTime);
      $quantity = ((float)$xml->attributes()->quantity);
      $price = ((float)$xml->attributes()->tradePrice);
      $proceeds = ((float)$xml->attributes()->proceeds);
      $fees = ((float)$xml->attributes()->ibCommission);
      $pnl = ((float)$xml->attributes()->fifoPnlRealized);
      $amount = ((float)$xml->attributes()->netCash);

      $stock = $this->findOrCreateStock($xml);

      // create trade statement if not exits
      $statement = $this->em->getRepository('App:StockTradeStatement')->findOneBy(
        [ 'stock' => $stock->getId(), 'portfolio' => $portfolio, 'date' => $date ]);
      if (!$statement) {
           $statement = (new StockTradeStatement())
              ->setPortfolio($portfolio)
              ->setTradeId($tradeId)
              ->setStock($stock)
              ->setName((string)$xml->attributes()->description)
              ->setDate($date)
              ->setCurrency($currency)
              ->setQuantity($quantity)
              ->setPrice($price)
              ->setProceeds($proceeds)
              ->setFees($fees)
              ->setAmount($amount)
              ->setRealizedPNL($pnl)
              ;

          if ((string)($xml->attributes()->openCloseIndicator) == 'O') {
            $statement->setStatus(Statement::OPEN_STATUS);
            $description = 'Opening ';
          } elseif ((string)$xml->attributes()->openCloseIndicator == 'C') {
            $statement->setStatus(Statement::CLOSE_STATUS);
            $description = 'Closing ';
          } else {
            print_r($xml);
          }
          $description = $description . $quantity . ' ' . $symbol . '@' . $price . $currency;
          /*
          if (in_array('A', $codes)) {
                $statement->setStatus(Statement::ASSIGNED_STATUS);
          } elseif (in_array('Ep', $codes)) {
              $statement->setStatus(Statement::EXPIRED_STATUS);
          } elseif (in_array('C', $codes)) {
              $statement->setStatus(Statement::CLOSE_STATUS);
          } elseif (in_array('O', $codes)) {
              $statement->setStatus(Statement::OPEN_STATUS);
          } else {
              print_r($codes);
          }
          */
          $statement->setDescription($description);

          $this->em->persist($statement);
      } else {
//          print($symbol . ' statement already exists');
      }
    }
  }

  // Trades,Data,DataDiscriminator,Asset Category,Currency,Symbol,Date/Time,Quantity,T. Price,Proceeds,Comm/Fee,Basis,Realized P/L,Realized P/L %,Code
  private function processOptionTrade(Portfolio $portfolio, \SimpleXMLElement $xml): void {
    $tradeId = intval((string)$xml->attributes()->transactionID);
//print("tradeId: " . $tradeId . "\n");
    $statement = $this->em->getRepository('App:OptionTradeStatement')->findOneBy(
      [ 'portfolio' => $portfolio, 'tradeId' => $tradeId ]);
    if (!$statement) {
      $currency = (string)$xml->attributes()->currency;
      $symbol = (string)$xml->attributes()->symbol;
      $date = new \DateTime((string)$xml->attributes()->dateTime);
      $quantity = ((float)$xml->attributes()->quantity);
      $price = ((float)$xml->attributes()->tradePrice);
      $proceeds = ((float)$xml->attributes()->proceeds);
      $fees = ((float)$xml->attributes()->ibCommission);
      $pnl = ((float)$xml->attributes()->fifoPnlRealized);
      $amount = ((float)$xml->attributes()->netCash);

      $option = $this->findOrCreateOption($xml);
      $stock = $option->getStock();

      // create trade statement if not exits
      $statement = $this->em->getRepository('App:OptionTradeStatement')->findOneBy(
        [ 'contract' => $option->getId(), 'portfolio' => $portfolio, 'date' => $date ]);
      if (!$statement) {
           $statement = (new OptionTradeStatement())
              ->setPortfolio($portfolio)
              ->setTradeId($tradeId)
              ->setStock($stock)
              ->setContract($option)
              ->setDate($date)
              ->setCurrency($currency)
              ->setQuantity($quantity)
              ->setPrice($price)
              ->setProceeds($proceeds)
              ->setFees($fees)
              ->setAmount($amount)
              ->setRealizedPNL($pnl)
              ;

          if ((string)$xml->attributes()->openCloseIndicator == 'O') {
            $statement->setStatus(Statement::OPEN_STATUS);
            $description = 'Opening ';
          } elseif ((string)$xml->attributes()->openCloseIndicator == 'C') {
            $statement->setStatus(Statement::CLOSE_STATUS);
            $description = 'Closing ';
          } else {
            print_r($xml);
          }
          $description = $description . $quantity . ' ' . (string)$xml->attributes()->description . '@' . $price . $currency;
          /*
          if (in_array('A', $codes)) {
                $statement->setStatus(Statement::ASSIGNED_STATUS);
          } elseif (in_array('Ep', $codes)) {
              $statement->setStatus(Statement::EXPIRED_STATUS);
          } elseif (in_array('C', $codes)) {
              $statement->setStatus(Statement::CLOSE_STATUS);
          } elseif (in_array('O', $codes)) {
              $statement->setStatus(Statement::OPEN_STATUS);
          } else {
              print_r($codes);
          }
          */
          $statement->setDescription($description);

          $this->em->persist($statement);
        }
      }
  }

  public function processTrade(\SimpleXMLElement $xml): void
  {
    $portfolio = $this->findOrCreatePortfolio((string)$xml->attributes()->accountId);
    if ($xml->attributes()->assetCategory == "STK") {
      $this->processStockTrade($portfolio, $xml);
    } elseif ($xml->attributes()->assetCategory == "OPT") {
      $this->processOptionTrade($portfolio, $xml);
    } else {
      print_r($xml);
    }
    $this->em->flush();
  }

}
