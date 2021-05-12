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
        $report = $xml->attributes()->queryName;

        $importer = new ImporterXml($this->em);

        if (isset($xml->FlexStatements->FlexStatement->Trades)) {
          $trades_count = sizeof($xml->FlexStatements->FlexStatement->Trades->Trade);
        } else {
          $trades_count = 0;
        }
        if (isset($xml->FlexStatements->FlexStatement->SecuritiesInfo)) {
          $securities_count = sizeof($xml->FlexStatements->FlexStatement->SecuritiesInfo->SecurityInfo);
        } else {
          $securities_count = 0;
        }
        if (isset($xml->FlexStatements->FlexStatement->CashTransactions)) {
          $cash_count = sizeof($xml->FlexStatements->FlexStatement->CashTransactions->CashTransaction);
        } else {
          $cash_count = 0;
        }
        if (isset($xml->FlexStatements->FlexStatement->TransactionTaxes)) {
          $taxes_count = sizeof($xml->FlexStatements->FlexStatement->TransactionTaxes->TransactionTax);
        } else {
          $taxes_count = 0;
        }
        if (($trades_count + $securities_count + $cash_count + $taxes_count) > 0) {
          $io->progressStart($trades_count + $securities_count + $cash_count + $taxes_count);
          for ($i=0; $i < $securities_count; $i++) {
            $importer->processSecurityInfo($xml->FlexStatements->FlexStatement->SecuritiesInfo->SecurityInfo[$i]);
            $io->progressAdvance();
          }
          for ($i=0; $i < $trades_count; $i++) {
            $importer->processTrade($xml->FlexStatements->FlexStatement->Trades->Trade[$i]);
            $io->progressAdvance();
          }
          for ($i=0; $i < $cash_count; $i++) {
            $importer->processCashTransaction($xml->FlexStatements->FlexStatement->CashTransactions->CashTransaction[$i]);
            $io->progressAdvance();
          }
          for ($i=0; $i < $taxes_count; $i++) {
            $importer->processTransactionTax($xml->FlexStatements->FlexStatement->TransactionTaxes->TransactionTax[$i]);
            $io->progressAdvance();
          }
          $io->progressFinish();
          $io->success($report . ' report loaded!');
        } else {
          $io->error($xml->ErrorMessage);
        }
      } elseif ($xml) {
        $io->error($xml->ErrorMessage);
      } else {
        $io->error('You have a new command! Now make it your own! Pass --help to see your options.');
      }
      return 0;
    }
}
