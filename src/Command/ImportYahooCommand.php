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

        foreach ($currencies as $currency) {
        	$rate = $client->getExchangeRate($currency->getBase(), $currency->getCurrency());
        	if ($rate) {
        		$currency->setRate($rate->getRegularMarketPrice());
        	} else {
        		$io->note(sprintf("Unknown currency: %s.%s\n", $currency->getBase(), $currency->getCurrency()));
        	}
        	$io->progressAdvance();
        }

        foreach ($stocks as $key => $contract) {
        	$ticker = $contract->getYahooTicker();
        	$quote = $client->getQuote($ticker);
            /*
            if ($contract->getSymbol() == 'AMZN') {
                print_r($quote);
            }
            */
        	if ($quote && ($contract->getCurrency() == 'USD' || $contract->getExchange())) {
        		if ($quote->getCurrency() == 'GBp') {
                    $contract->setCurrency('GBP');
        		} elseif ($quote->getCurrency()) {
                    $contract->setCurrency($quote->getCurrency());
        		}
                $contract->setPrice(self::getYahooPrice($quote));
                $contract->setAsk(($quote->getCurrency() == 'GBp') ? ($quote->getAsk() / 100) : $quote->getAsk());
                $contract->setBid(($quote->getCurrency() == 'GBp') ? ($quote->getBid() / 100) : $quote->getBid());
                $contract->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());
                /*
                if ($ticker == 'AMZN') {
                  print_r($quote);
                  printf("%s: %f %f %f\n", $ticker, self::getYahooPrice($quote), $quote->getAsk(), $quote->getBid());
                  printf("%s: %f %f %f\n", $ticker, $contract->getPrice(), $contract->getAsk(), $contract->getBid());
                }
                */
                $contract->setName($quote->getLongName());
                $contract->setDividendTTM($quote->getTrailingAnnualDividendRate());
                $contract->setFiftyTwoWeekLow($quote->getFiftyTwoWeekLow());
                $contract->setFiftyTwoWeekHigh($quote->getFiftyTwoWeekHigh());
                $contract->setEpsTTM($quote->getEpsTrailingTwelveMonths());
                $contract->setEpsForward($quote->getEpsForward());
        	} else {
        		$io->note(sprintf("Unknown quote: %s on %s\n", $ticker, $contract->getExchange()));
        	}
          // save / write the changes to the database
          $this->em->flush();
          $io->progressAdvance();
          if ($key % 10 == 0) sleep(1);
        }

        $echeance = (new \DateTime())->sub(new \DateInterval('P1D'));
        foreach ($options as $key => $contract) {
          if ($contract->getLastTradeDate() > $echeance) {
          	$ticker = $contract->getYahooTicker();
          	$quote = $client->getQuote($ticker);
            if ($quote) {
              $contract->setPrice(self::getYahooPrice($quote));
              $contract->setAsk(($quote->getCurrency() == 'GBp') ? ($quote->getAsk() / 100) : $quote->getAsk());
              $contract->setBid(($quote->getCurrency() == 'GBp') ? ($quote->getBid() / 100) : $quote->getBid());
              $contract->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());
          	} else {
          		$io->note(sprintf("Unknown quote: %s on %s\n", $ticker, $contract->getExchange()));
          	}
          } else {
//            $io->note(sprintf("Expired option %s: %s < %s\n", $contract->getYahooTicker(), $contract->getLastTradeDate()->format('Y-m-d H:i:s'), $echeance->format('Y-m-d H:i:s')));
            $contract->setPrice(null);
            $contract->setAsk(null);
            $contract->setBid(null);
            $contract->setPreviousClosePrice(null);
          }
          // save / write the changes to the database
          $this->em->flush();
          $io->progressAdvance();
          if ($key % 11 == 0) sleep(1);
        }

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
