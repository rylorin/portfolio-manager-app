<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use GuzzleHttp\Client;

/*
 * https://github.com/scheb/yahoo-finance-api
 */
class ImportYahooCommand extends Command
{
    protected static $defaultName = 'app:import:yahoo';

    /**
     * AppImportIbCommand constructor.
     *
     * @param EntityManagerInterface $em
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(EntityManagerInterface $em)
    {
    	parent::__construct();
    	$this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update some financial information from Yahoo')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create a new client from the factory
        $client = ApiClientFactory::createApiClient();
        $currencies = $this->em->getRepository('App:Currency')->findAll();
        $stocks = $this->em->getRepository('App:Stock')->findAll();
        $options = $this->em->getRepository('App:Option')->findAll();
        $io->progressStart(sizeof($stocks) + sizeof($options) + sizeof($currencies));

        /*
        // Returns an array of Scheb\YahooFinanceApi\Results\SearchResult
        $searchResult = $client->search("CLL");
        foreach ($searchResult as $result) {
          $io->note($result->getSymbol());
          $io->note($result->getType());
          $io->note($result->getTypeDisp());
        }
        $quote = $client->getQuote('AAPL');
        print_r($quote);
        */

        $query = [];
        foreach ($currencies as $currency) {
          $query[] = [$currency->getBase(), $currency->getCurrency()];
        }
        $result = $client->getExchangeRates($query);
        foreach ($result as $rate) {
          foreach ($currencies as $currency) {
            if ($rate->getShortName() == ($currency->getBase() . '/' . $currency->getCurrency())) {
              $currency->setRate($rate->getRegularMarketPrice());
              break;
            }
          }
          $io->progressAdvance();
        }
        $this->em->flush();

        $query = [];
        foreach ($stocks as $key => $contract) {
          $query[] = $contract->getYahooTicker();
        }
        $result = $client->getQuotes($query);
        foreach ($result as $quote) {
          foreach ($stocks as $key => $contract) {
            if ($contract->getYahooTicker() == $quote->getSymbol()) {
              if ($quote->getCurrency() == 'GBp') {
                      $contract->setCurrency('GBP');
              } elseif ($quote->getCurrency()) {
                      $contract->setCurrency($quote->getCurrency());
              }
              $contract->setPrice(self::getYahooPrice($quote));
              $contract->setAsk(($quote->getCurrency() == 'GBp') ? ($quote->getAsk() / 100) : $quote->getAsk());
              $contract->setBid(($quote->getCurrency() == 'GBp') ? ($quote->getBid() / 100) : $quote->getBid());
              $contract->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());
              $contract->setName($quote->getLongName());
              $contract->setDividendTTM($quote->getTrailingAnnualDividendRate());
              $contract->setFiftyTwoWeekLow($quote->getFiftyTwoWeekLow());
              $contract->setFiftyTwoWeekHigh($quote->getFiftyTwoWeekHigh());
              $contract->setEpsTTM($quote->getEpsTrailingTwelveMonths());
              $contract->setEpsForward($quote->getEpsForward());
              break;
            }
          }
          $io->progressAdvance();
        }
        $this->em->flush();

        $query = [];
        $echeance = (new \DateTime())->sub(new \DateInterval('P1D'));
        foreach ($options as $key => $contract) {
          if ($contract->getLastTradeDate() > $echeance) {
            $query[] = $contract->getYahooTicker();
          } else {
            $contract->setPrice(null);
            $contract->setAsk(null);
            $contract->setBid(null);
            $contract->setPreviousClosePrice(null);
          }
        }
        $result = $client->getQuotes($query);
        foreach ($result as $quote) {
          foreach ($options as $key => $contract) {
            if ($contract->getYahooTicker() == $quote->getSymbol()) {
              $contract->setPrice(self::getYahooPrice($quote));
              $contract->setAsk(($quote->getCurrency() == 'GBp') ? ($quote->getAsk() / 100) : $quote->getAsk());
              $contract->setBid(($quote->getCurrency() == 'GBp') ? ($quote->getBid() / 100) : $quote->getBid());
              $contract->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());
              break;
            }
          }
          $io->progressAdvance();
        }
        $this->em->flush();

        $io->progressFinish();
        $io->success('Contracts infos updated using Yahoo finance.');
        return 0;
    }

    private static function getYahooPrice($quote) {
      if ($quote->getMarketState() == 'PRE') {
        $price = $quote->getPreMarketPrice() ? $quote->getPreMarketPrice() : $quote->getRegularMarketPrice();
    } elseif ($quote->getMarketState() == 'POST') {
        $price = $quote->getPostMarketPrice() ? $quote->getPostMarketPrice() : $quote->getRegularMarketPrice();
      } elseif ($quote->getMarketState() == 'REGULAR') {
        $price = $quote->getRegularMarketPrice();
      } elseif ($quote->getMarketState() == 'PREPRE') {
        $price = $quote->getRegularMarketPrice();
      } elseif ($quote->getMarketState() == 'POSTPOST') {
        $price = $quote->getRegularMarketPrice();
      } elseif ($quote->getMarketState() == 'CLOSED') {
        $price = $quote->getRegularMarketPrice();
      } else {
        $price = $quote->getRegularMarketPrice();
//        print_r($quote);
      }
      return ($quote->getCurrency() == 'GBp') ? $price / 100 : $price;
    }

}
