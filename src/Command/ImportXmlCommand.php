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

class ImportXmlCommand extends Command
{
    protected static $defaultName = 'app:import:xml';
    protected static $defaultDescription = 'Import an Flex report XML file';
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
            ->addArgument('file', InputArgument::REQUIRED, 'IB flex web service security token')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
      $io = new SymfonyStyle($input, $output);
      $file = $input->getArgument('file');

      $io->note(sprintf('Processing query id: %s', $query));

      $xml = simplexml_load_file($file);
      if ($xml) {
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
      } else {
        $io->error('You have a new command! Now make it your own! Pass --help to see your options.');
      }
      return 0;
    }
}
