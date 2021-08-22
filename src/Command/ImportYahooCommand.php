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
        $query_currencies = [];
        foreach ($currencies as $currency) {
          $query_currencies[] = [$currency->getBase(), $currency->getCurrency()];
        }

        $stocks = $this->em->getRepository('App:Stock')->findAll();
        $query_stocks = [];
        foreach ($stocks as $key => $contract) {
          $query_stocks[] = $contract->getYahooTicker();
        }

        $options = $this->em->getRepository('App:Option')->findAll();
        $query_options = [];
        $echeance = (new \DateTime())->sub(new \DateInterval('P1D'));
        foreach ($options as $key => $contract) {
          if ($contract->getLastTradeDate() > $echeance) {
            $query_options[] = $contract->getYahooTicker();
          } else {
            $contract->setPrice(null);
            $contract->setAsk(null);
            $contract->setBid(null);
            $contract->setPreviousClosePrice(null);
          }
        }

        $io->progressStart(sizeof($query_currencies) + sizeof($query_stocks) + sizeof($query_options));

        /*
        // Returns an array of Scheb\YahooFinanceApi\Results\SearchResult
        $searchResult = $client->search("CLL");
        foreach ($searchResult as $result) {
          $io->note($result->getSymbol());
          $io->note($result->getType());
          $io->note($result->getTypeDisp());
        }

        $contract = $this->em->getRepository('App:Stock')->findOneBySymbol('ISPA');
        print($contract->getYahooTicker());
        $quote = $client->getQuote($contract->getYahooTicker());
        print_r($quote);
        */

        $result = $client->getExchangeRates($query_currencies);
        foreach ($result as $rate) {
          foreach ($currencies as $currency) {
            if ($rate->getShortName() == ($currency->getBase() . '/' . $currency->getCurrency())) {
              $currency->setRate($rate->getRegularMarketPrice());
//              print($rate->getShortName());
              break;
            }
          }
          $io->progressAdvance();
        }
        $this->em->flush();

        $result = $client->getQuotes($query_stocks);
        foreach ($result as $quote) {
          foreach ($stocks as $key => $contract) {
            if ($contract->getYahooTicker() == $quote->getSymbol()) {
              if (!$contract->getCurrency()) {
                if ($quote->getCurrency() == 'GBp') {
                        $contract->setCurrency('GBP');
                } elseif ($quote->getCurrency()) {
                        $contract->setCurrency($quote->getCurrency());
                }
              }
              $contract->setPrice(self::getYahooPrice($quote));
              $contract->setAsk(($quote->getCurrency() == 'GBp') ? ($quote->getAsk() / 100) : $quote->getAsk());
              $contract->setBid(($quote->getCurrency() == 'GBp') ? ($quote->getBid() / 100) : $quote->getBid());
              $contract->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());
              if (!$contract->getName()) $contract->setName($quote->getLongName());
              $contract->setDividendTTM($quote->getTrailingAnnualDividendRate());
              $contract->setFiftyTwoWeekLow($quote->getFiftyTwoWeekLow());
              $contract->setFiftyTwoWeekHigh($quote->getFiftyTwoWeekHigh());
              $contract->setEpsTTM($quote->getEpsTrailingTwelveMonths());
              $contract->setEpsForward($quote->getEpsForward());
/*
              if ($quote->getSymbol() == 'AMD') {
                printf("\n");
                print_r($quote);
                print($quote->getSymbol() . '/' . $contract->getSymbol() . '/' . $contract->getId() . ' = ' . $contract->getPrice());
                printf("\n");
              }
*/
              break;
            }
          }
          $io->progressAdvance();
        }
        $this->em->flush();
        
        while (sizeof($query_options)) {
          $options_to_query = array();
          for ($i=0; ($i < 512) && sizeof($query_options); $i++) {
            array_push($options_to_query, array_pop($query_options));
          }
          $result = $client->getQuotes($options_to_query);
          foreach ($result as $quote) {
            foreach ($options as $key => $contract) {
              if ($contract->getYahooTicker() == $quote->getSymbol()) {
                $dv = new \DateInterval('PT'.$quote->getExchangeDataDelayedBy().'M');
                $updated = (new \DateTime())->sub($dv);
                // print($updated->format('Y-m-d h:i'));
                $contract->setPrice(self::getYahooPrice($quote));
                $contract->setAsk(($quote->getCurrency() == 'GBp') ? ($quote->getAsk() / 100) : $quote->getAsk());
                $contract->setBid(($quote->getCurrency() == 'GBp') ? ($quote->getBid() / 100) : $quote->getBid());
                $contract->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());
                $contract->setUpdated($updated);
                break;
              }
            }
            $io->progressAdvance();
          }
          $this->em->flush();
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
