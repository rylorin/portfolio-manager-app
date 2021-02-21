<?php

// https://financialmodelingprep.com

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Rylorin\FmtFinanceApi\ApiClient;
use Rylorin\FmtFinanceApi\ApiClientFactory;

class ImportFmtCommand extends Command
{
    protected static $defaultName = 'app:import:fmt';

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
        	->setDescription('Update some financial information from Financial Modeling Prep')
//        	->addArgument('apikey', InputArgument::REQUIRED, 'Your personal API key')
            ->addOption('apikey', null, InputOption::VALUE_REQUIRED, 'Your personal API key')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
    	$apikey = 'demo';
        $io = new SymfonyStyle($input, $output);
        /*
        $arg1 = $input->getArgument('apikey');
        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
            $apikey = $arg1;
        }
        */
        if ($input->getOption('apikey')) {
            $apikey = $input->getOption('apikey');
        } else {
            $apikey = 'demo';
        }
        // Create a new client from the factory
        $client = ApiClientFactory::createApiClient($apikey);

//        $quotes = $client->getQuotes(['AAPL','AMZN']);
// print_r($quotes);
        $quote = $client->getQuote('AAPL');
        print_r($quote);
        return 1;

        $stocks = $this->em->getRepository('App:Stock')->findAll();
        $io->progressStart(sizeof($stocks));

        foreach ($stocks as $stock) {
        	$ticker = $stock->getYahooTicker();
        	$quote = $client->getQuote($ticker);
        	if ($quote) {
        		$stock->setPrice($quote->getPrice());
        	} else {
        		$io->note(sprintf("Unknown quote: %s on %s\n", $ticker, $stock->getExchange()));
        	}
//        	$io->progressAdvance();
        }

        // save / write the changes to the database
        $this->em->flush();
        $io->progressFinish();

        $io->success('Stocks information updated using FMT finance.');
        return 0;
    }
}
