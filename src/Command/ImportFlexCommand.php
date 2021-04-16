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
use App\Importer\ImporterXml;

class ImportFlexCommand extends Command
{
    protected static $defaultName = 'app:import:flex';
    protected static $defaultDescription = 'Import a Flex report using Web Service';
    protected EntityManagerInterface $em;

    /**
     * ImportXmlCommand constructor.
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
            ->setDescription(self::$defaultDescription)
            ->addArgument('token', InputArgument::REQUIRED, 'IB flex web service security token')
            ->addArgument('query', InputArgument::REQUIRED, 'IB flex query')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
      $io = new SymfonyStyle($input, $output);
      $token = $input->getArgument('token');
      $query = $input->getArgument('query');

      $io->note(sprintf('Processing query id: %s', $query));

      $xml = simplexml_load_file(
        sprintf("https://gdcdyn.interactivebrokers.com/Universal/servlet/FlexStatementService.SendRequest?t=%s&q=%s&v=3", $token, $query));
      if ($xml && ($xml->Status == "Success")) {
        $xml = simplexml_load_file(sprintf("%s?q=%s&t=%s&v=3", $xml->Url, $xml->ReferenceCode, $token));
//        print_r($xml);
        $report = $xml->attributes()->queryName;

        $importer = new ImporterXml($this->em);
// print_r($xml);
        $trades_count = sizeof($xml->FlexStatements->FlexStatement->Trades->Trade);
        $io->progressStart($trades_count);
        for ($i=0; $i < $trades_count; $i++) {
//          print('Trade[' . $i . "]\n");
//          print_r((string)$xml->FlexStatements->FlexStatement->Trades->Trade[$i]->attributes()->transactionID);
          $importer->processTrade($xml->FlexStatements->FlexStatement->Trades->Trade[$i]);
          $io->progressAdvance();
        }
        $io->progressFinish();

        $io->success($report . ' report loaded!');
      } elseif ($xml) {
        $io->error($xml->ErrorMessage);
      } else {
        $io->error('You have a new command! Now make it your own! Pass --help to see your options.');
      }
      return 0;
    }
}
