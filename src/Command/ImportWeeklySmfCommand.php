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
use League\Csv\Reader;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use GuzzleHttp\Client;
use App\Entity\Contract;
use App\Entity\Stock;
use App\Entity\Option;
use App\Entity\Position;
use App\Entity\Balance;
use App\Entity\Portfolio;
use App\Entity\Currency;

/*
 *  Weekly options data from:
 *  http://www.cboe.com/products/weeklys-options/available-weeklys
 */
class ImportWeeklySmfCommand extends Command
{
    protected const MATURITIES = 1;
    protected const DISCOUNT = 0.08;

    protected static $defaultName = 'app:import:weeklysmf';
    protected $em;
    protected $client;
    protected $maturities = [];
    protected $createMode;
    protected $io;

    /**
     * ImportIbCommand constructor.
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
            ->setDescription('Imports the mock CSV data file')
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file to import')
            ->addOption('create', 'c', InputOption::VALUE_NONE, 'Create missing stocks contracts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//      print_r($input->getOption('create'));
        $reportType = 'unknown';
        $this->io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('file');
        if ($arg1) {
            $this->io->note(sprintf('Processing file: %s', $arg1));
        }
        $this->createMode = $input->getOption('create');
        if ($this->createMode) {
          $this->io->note('Running in create mode');
        }

        // Create a new client from the factory
        $this->client = ApiClientFactory::createApiClient();
        // And the CVS reader
        $reader = Reader::createFromPath($arg1);
        $reader->setDelimiter(';');

        $records = $reader->getRecords();
        // Don't put this line in foreach loop or it will break! :o
        $this->io->progressStart(iterator_count($records));
        foreach ($records as $offset => $record) {
          if ($offset == 0) {
            $reportType = $record[0];
          }
          elseif ($record[2] == 'Expanded Weeklys Available Expirations:') {
            $this->processMaturities($record);
          }
          elseif (($record[3] == 'Equity' || $record[3] == 'ETF') && ($record[6] == 'X')) {
            $this->processStockLine($record);
          }
          else {
//          	printf("ignored: '%s','%s','%s','%s','%s','%s','%s','%s'\n",
//              $record[0], $record[1], $record[2], $record[3], $record[4], $record[5], $record[6], $record[7]);
          }
          // save / write the changes to the database
          $this->em->flush();
          $this->io->progressAdvance();
          if ($offset % 10 == 0) sleep(2);
        }
        $this->io->progressFinish();
        $this->io->success($reportType . ' loaded!');
        return 0;
    }

    private function processMaturities($record): void {
//      printf("processMaturities: '%s','%s','%s','%s','%s','%s','%s','%s'\n",
//        $record[3], $record[4], $record[5], $record[6], $record[7], $record[8], $record[9], $record[10]);
      $now = (new \DateTime('now'));
      for ($i = 3; $i <= 10; $i++) {
          $maturity = \DateTime::createFromFormat('m/d/y', $record[$i]);
          if ($maturity > $now) {
            array_push ( $this->maturities , $maturity );
          }
      }
//      print_r($this->maturities);
    }

    private function processStockLine($record): void {
//      printf("processStockLine: '%s','%s','%s','%s','%s','%s','%s','%s'\n",
//        $record[0], $record[1], $record[2], $record[3], $record[4], $record[5], $record[6], $record[7]);
      $stock = $this->em->getRepository('App:Stock')->findOneBy([ 'symbol' => str_replace('/', '-', $record[0]) ]);
      if (!$stock && $this->createMode) {
        $stock = (new Stock(str_replace('/', '-', $record[0])))
          ->setCurrency('USD')
          ->setName($record[2])
          ;
        $this->em->persist($stock);
      }
      if ($stock) {
        $quote = $this->client->getQuote(str_replace('/', '-', $record[0]));
        $price = $quote->getRegularMarketPrice();
        $stock->setPrice($price);
        $stock->setAsk(($quote->getCurrency() == 'GBp') ? ($quote->getAsk() / 100) : $quote->getAsk());
        $stock->setBid(($quote->getCurrency() == 'GBp') ? ($quote->getBid() / 100) : $quote->getBid());
        $stock->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());

        for ($i = 0; $i < self::MATURITIES; $i++) {          // loop for maturities (next 1 maturities)
          // init strike here
          $strike = $price * (1 - self::DISCOUNT);
          if ($strike < 50) {
            $strike = floor($strike / 0.5) * 0.5;
          } elseif ($strike < 100) {
            $strike = floor($strike);
          } elseif ($strike < 50) {
            $strike = floor($strike / 2.5) * 2.5;
          } else {
            $strike = floor($strike / 10) * 10;
          }
          while ($strike < $price) {    // loop for stikes
            $contract = (new Option())
              ->setCurrency('USD')
              ->setExchange('CBOE')
              ->setStock($stock)
              ->setLastTradeDate($this->maturities[$i])
              ->setStrike($strike)
              ->setCallOrPut('P')
              ;
            $option = $this->em->getRepository('App:Option')->findOneBy([ 'symbol' => $contract->getSymbol() ]);
            if (!$option) {
              // option not found in Repository
              $quote = $this->client->getQuote($contract->getYahooTicker());
              if ($quote) {
                // existing Yahoo quote, add it to Repository
                $option = $contract;
                printf("creating option: %s\n", $option->getSymbol());
                $this->em->persist($option);
              } else {
                $this->io->note(sprintf("quote not found: %s\n", $contract->getYahooTicker()));
              }
            } else {
              printf("existing option: %s\n", $option->getSymbol());
              $quote = $this->client->getQuote($option->getYahooTicker());
            }
            if ($quote) {
              $option->setPrice($quote->getRegularMarketPrice())
                ->setAsk($quote->getAsk())
                ->setBid($quote->getBid())
                ->setPreviousClosePrice(($quote->getCurrency() == 'GBp') ? ($quote->getRegularMarketPreviousClose() / 100) : $quote->getRegularMarketPreviousClose());
            }
            // increase strike here
            if ($strike < 21) {
              $strike += 0.5;
            } elseif ($strike < 100) {
              $strike++;
            } elseif ($strike < 1000) {
              $strike += 2.5;
            } else {
              $strike += 10;
            }
          } // end loop strikes
        } // end loop maturities
      }
    }

}
