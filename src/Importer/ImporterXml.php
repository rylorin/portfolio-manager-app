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
    $stock = $this->em->getRepository('App:Stock')
      ->findOneBy([ 'conId' => (string)$xml->attributes()->conid ]);
    if (!$stock) {
      $symbol = Contract::normalizeSymbol((string)$xml->attributes()->symbol);
      $stock = $this->em->getRepository('App:Stock')
        ->findOneBy([ 'symbol' => $symbol ]);
      if (!$stock) {
        $stock = new Stock($symbol);
        $this->em->persist($stock);
      }
      $stock->setConId((int)$xml->attributes()->conid);
      // flush required therefore futurs lookup will succeed
//      $this->em->flush();
    }
    if ($xml->attributes()->listingExchange && ((string)$xml->attributes()->listingExchange != $stock->getExchange()))
      $stock->setExchange((string)$xml->attributes()->listingExchange);
    if ($xml->attributes()->currency && ((string)$xml->attributes()->currency != $stock->getCurrency()))
      $stock->setCurrency((string)$xml->attributes()->currency);
    if ($xml->attributes()->description && !$stock->getName())
      $stock->setName(html_entity_decode((string)$xml->attributes()->description));
    return $stock;
  }

  private function findOrCreateOption(\SimpleXMLElement $xml): Option {
    $contract = $this->em->getRepository('App:Option')
      ->findOneBy([ 'conId' => (string)$xml->attributes()->conid ]);
    if (!$contract) {
      $stock = $this->em->getRepository('App:Stock')
        ->findOneBy([ 'conId' => (string)$xml->attributes()->underlyingConid ]);
      if (!$stock) {
        $symbol = Contract::normalizeSymbol((string)$xml->attributes()->underlyingSymbol);
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
        $contract = (new Option((string)$xml->attributes()->description))
          ->setStock($stock)
          ->setLastTradeDate(new \DateTime((string)$xml->attributes()->expiry))
          ->setStrike((float)$xml->attributes()->strike)
          ->setCallOrPut((string)$xml->attributes()->putCall)
          ;

        $this->em->persist($contract);
      }
      $contract->setConId((int)$xml->attributes()->conid);
      // flush required therefore futurs lookup will succeed
//      $this->em->flush();
    }
//    print_r($xml);
    if ($xml->attributes()->listingExchange && ((string)$xml->attributes()->listingExchange != $contract->getExchange()))
      $contract->setExchange((string)$xml->attributes()->listingExchange);
    if ($xml->attributes()->currency && ((string)$xml->attributes()->currency != $contract->getCurrency()))
      $contract->setCurrency((string)$xml->attributes()->currency);
    if ($xml->attributes()->multiplier && ((string)$xml->attributes()->multiplier != $contract->getMultiplier()))
      $contract->setMultiplier((string)$xml->attributes()->multiplier);
    if ($xml->attributes()->symbol && !$contract->getName())
      $contract->setName((string)$xml->attributes()->symbol);
    return $contract;
  }

  private function processStockTrade(Portfolio $portfolio, \SimpleXMLElement $xml): void {
    $transactionID = intval((string)$xml->attributes()->transactionID);
//print("tradeId: " . $transactionID . "\n");
    $statement = $this->em->getRepository('App:StockTradeStatement')->findOneBy(
      [ 'portfolio' => $portfolio, 'tradeId' => $transactionID ]);
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
        [ 'stock' => $stock->getId(), 'portfolio' => $portfolio, 'date' => $date, 'tradeId' => null ]);
      if (!$statement) {
           $statement = (new StockTradeStatement())
              ->setPortfolio($portfolio)
              ->setStock($stock)
              ->setDate($date)
              ->setCurrency($currency)
              ->setQuantity($quantity)
              ->setPrice($price)
              ->setProceeds($proceeds)
              ->setFees($fees)
              ->setAmount($amount)
              ->setRealizedPNL($pnl)
              ;

          if ((string)($xml->attributes()->notes) == 'A') {
            $statement->setStatus(Statement::ASSIGNED_STATUS);
            $description = 'Assigned ';
          } elseif ((string)($xml->attributes()->notes) == 'Ep') {
            $statement->setStatus(Statement::EXPIRED_STATUS);
            $description = 'Expired ';
          } elseif ((string)($xml->attributes()->openCloseIndicator) == 'O') {
            $statement->setStatus(Statement::OPEN_STATUS);
            $description = 'Opening ';
          } elseif ((string)$xml->attributes()->openCloseIndicator == 'C') {
            $statement->setStatus(Statement::CLOSE_STATUS);
            $description = 'Closing ';
          } else {
            print_r($xml);
          }
          $description = $description . $quantity . ' ' . $symbol . '@' . $price . $currency;
          $statement->setDescription($description);

          $this->em->persist($statement);
      } else {
//          print($symbol . ' statement already exists');
      }
      $statement->setTransactionId($transactionID);
    }
    if ($xml->attributes()->fxRateToBase && !$statement->getfxRateToBase())
      $statement->setfxRateToBase((float)$xml->attributes()->fxRateToBase);
  }

  private function processOptionTrade(Portfolio $portfolio, \SimpleXMLElement $xml): void {
    $transactionID = intval((string)$xml->attributes()->transactionID);
//print("tradeId: " . $transactionID . "\n");
    $statement = $this->em->getRepository('App:OptionTradeStatement')->findOneBy(
      [ 'portfolio' => $portfolio, 'tradeId' => $transactionID ]);
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

          if ((string)($xml->attributes()->notes) == 'A') {
            $statement->setStatus(Statement::ASSIGNED_STATUS);
            $description = 'Assigned ';
          } elseif ((string)($xml->attributes()->notes) == 'Ep') {
            $statement->setStatus(Statement::EXPIRED_STATUS);
            $description = 'Expired ';
          } elseif ((string)($xml->attributes()->openCloseIndicator) == 'O') {
            $statement->setStatus(Statement::OPEN_STATUS);
            $description = 'Opening ';
          } elseif ((string)$xml->attributes()->openCloseIndicator == 'C') {
            $statement->setStatus(Statement::CLOSE_STATUS);
            $description = 'Closing ';
          } else {
            print_r($xml);
          }
          $description = $description . $quantity . ' ' . (string)$xml->attributes()->description . '@' . $price . $currency;
          $statement->setDescription($description);

          $this->em->persist($statement);
        }
        $statement->setTransactionId($transactionID);
      }
      if ($xml->attributes()->fxRateToBase && !$statement->getfxRateToBase())
        $statement->setfxRateToBase((float)$xml->attributes()->fxRateToBase);
  }

  private function processDividends(\SimpleXMLElement $xml): void {
    $portfolio = $this->findOrCreatePortfolio((string)$xml->attributes()->accountId);
    $transactionID = (int)$xml->attributes()->transactionID;
    $statement = $this->em->getRepository('App:OptionTradeStatement')->findOneBy(
      [ 'portfolio' => $portfolio, 'tradeId' => $transactionID ]);
    if (!$statement) {
      $description = (string)$xml->attributes()->description;
      $xml->attributes()->description = null;
      $stock = $this->findOrCreateStock($xml);
      $date = new \DateTime((string)$xml->attributes()->settleDate);
      $amount = (float)$xml->attributes()->amount;
      $statement = $this->em->getRepository('App:DividendStatement')->findOneBy(
        [ 'portfolio' => $portfolio, 'stock' => $stock->getId(), 'date' => $date, 'amount' => $amount ]);
      if (!$statement) {
        $currency = (string)$xml->attributes()->currency;
        $country = substr($description, strpos($description, '(') + 1, 2);
        $statement = (new DividendStatement())
            ->setPortfolio($portfolio)
            ->setStock($stock)
            ->setDate($date)
            ->setDescription($description)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setCountry($country)
            ;
        $this->em->persist($statement);
      }
      $statement->setTransactionId($transactionID);
    }
    if ($xml->attributes()->fxRateToBase && !$statement->getfxRateToBase())
      $statement->setfxRateToBase((float)$xml->attributes()->fxRateToBase);
  }

  private function processWithholdingTax(\SimpleXMLElement $xml): void {
    $portfolio = $this->findOrCreatePortfolio((string)$xml->attributes()->accountId);
    $transactionID = (int)$xml->attributes()->transactionID;
    $statement = $this->em->getRepository('App:OptionTradeStatement')->findOneBy(
      [ 'portfolio' => $portfolio, 'tradeId' => $transactionID ]);
    if (!$statement) {
      $description = (string)$xml->attributes()->description;
      $xml->attributes()->description = null;
      $stock = $this->findOrCreateStock($xml);
      $date = new \DateTime((string)$xml->attributes()->settleDate);
      $amount = (float)$xml->attributes()->amount;
      $statement = $this->em->getRepository('App:TaxStatement')->findOneBy(
        [ 'portfolio' => $portfolio, 'stock' => $stock->getId(), 'date' => $date, 'amount' => $amount ]);
      if (!$statement) {
        $currency = (string)$xml->attributes()->currency;
        $country = substr($description, strpos($description, '(') + 1, 2);
        $statement = (new TaxStatement())
            ->setPortfolio($portfolio)
            ->setStock($stock)
            ->setDate($date)
            ->setDescription($description)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setCountry($country)
            ;
        $this->em->persist($statement);
      }
      $statement->setTransactionId($transactionID);
    }
    if ($xml->attributes()->fxRateToBase && !$statement->getfxRateToBase())
      $statement->setfxRateToBase((float)$xml->attributes()->fxRateToBase);
  }

  private function processBrokerInterest(\SimpleXMLElement $xml): void {
    $portfolio = $this->findOrCreatePortfolio((string)$xml->attributes()->accountId);
    $transactionID = (int)$xml->attributes()->transactionID;
    $statement = $this->em->getRepository('App:InterestStatement')->findOneBy(
      [ 'portfolio' => $portfolio, 'tradeId' => $transactionID ]);
    if (!$statement) {
      $date = new \DateTime((string)$xml->attributes()->dateTime);
      $amount = (float)$xml->attributes()->amount;
      $statement = $this->em->getRepository('App:InterestStatement')->findOneBy(
        [ 'portfolio' => $portfolio, 'date' => $date, 'amount' => $amount ]);
      if (!$statement) {
        $currency = (string)$xml->attributes()->currency;
        $description = (string)$xml->attributes()->description;
        $statement = (new InterestStatement())
            ->setPortfolio($portfolio)
            ->setDate($date)
            ->setDescription($description)
            ->setAmount($amount)
            ->setCurrency($currency)
            ;
        $this->em->persist($statement);
      }
      $statement->setTransactionId($transactionID);
    }
    if ($xml->attributes()->fxRateToBase && !$statement->getfxRateToBase())
      $statement->setfxRateToBase((float)$xml->attributes()->fxRateToBase);
  }

  public function processTransactionTax(\SimpleXMLElement $xml): void {
    $portfolio = $this->findOrCreatePortfolio((string)$xml->attributes()->accountId);
      $stock = $this->findOrCreateStock($xml);
      $date = new \DateTime((string)$xml->attributes()->settleDate);
      $amount = (float)$xml->attributes()->amount;
      $statement = $this->em->getRepository('App:FeeStatement')->findOneBy(
        [ 'portfolio' => $portfolio, 'stock' => $stock->getId(), 'date' => $date, 'amount' => $amount ]);
      if (!$statement) {
        $currency = (string)$xml->attributes()->currency;
        $description = (string)$xml->attributes()->taxDescription;
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
    if ($xml->attributes()->fxRateToBase && !$statement->getfxRateToBase())
      $statement->setfxRateToBase((float)$xml->attributes()->fxRateToBase);
  }

  public function processTrade(\SimpleXMLElement $xml): void
  {
    $portfolio = $this->findOrCreatePortfolio((string)$xml->attributes()->accountId);
    if ($xml->attributes()->assetCategory == "STK") {
      $this->processStockTrade($portfolio, $xml);
    } elseif ($xml->attributes()->assetCategory == "OPT") {
      $this->processOptionTrade($portfolio, $xml);
    } elseif ($xml->attributes()->assetCategory == "CASH") {
      // silently ignore for the moment
    } else {
      print_r($xml);
    }
    $this->em->flush();
  }

  public function processCashTransaction(\SimpleXMLElement $xml): void
  {
    if ($xml->attributes()->type == "Withholding Tax") {
      $this->processWithholdingTax($xml);
    } elseif (($xml->attributes()->type == "Dividends") || ($xml->attributes()->type == "Payment In Lieu Of Dividends")) {
      $this->processDividends($xml);
    } elseif (($xml->attributes()->type == "Broker Interest Paid") || ($xml->attributes()->type == "Broker Interest Received")) {
      $this->processBrokerInterest($xml);
    } elseif ($xml->attributes()->type == "Deposits/Withdrawals") {
      // silently ignore for the moment
    } elseif ($xml->attributes()->type == "Other Fees") {
      // silently ignore for the moment
    } else {
      print_r($xml);
    }
    $this->em->flush();
  }

  public function processSecurityInfo(\SimpleXMLElement $xml): void
  {
    if ($xml->attributes()->assetCategory == "STK") {
      $this->findOrCreateStock($xml);
    } elseif ($xml->attributes()->assetCategory == "OPT") {
      $this->findOrCreateOption($xml);
    } else {
      print_r($xml);
    }
    $this->em->flush();
  }

}
