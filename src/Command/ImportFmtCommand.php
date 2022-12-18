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
    private $em;

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
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('apikey')) {
            $apikey = $input->getOption('apikey');
        } else {
            $apikey = 'demo';
        }
        // Create a new client from the factory
        $client = ApiClientFactory::createApiClient($apikey);

        $stocks = $this->em->getRepository('App:Stock')->findAll();
        $io->progressStart(sizeof($stocks));

        foreach ($stocks as $stock) {
            $ticker = $stock->getYahooTicker();
            $quote = $client->getQuote($ticker);
            print_r($quote);
            if ($quote) {
                $stock->setPrice($quote->getPrice());
                return 1;
            } else {
                $io->note(sprintf("Unknown quote: %s on %s\n", $ticker, $stock->getExchange()));
            }
            $io->progressAdvance();
        }

        // save / write the changes to the database
        $this->em->flush();
        $io->progressFinish();

        $io->success('Stocks information updated using FMT finance.');
        return 0;
    }
}