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
use App\Importer\ImporterIB;

/*
 *  With ideas from: https://codereviewvideos.com/course/how-to-import-a-csv-in-symfony
 *  CSV importer: https://csv.thephpleague.com/9.0/
 */
class ImportIbCommand extends Command
{
    protected static $defaultName = 'app:import:ib';
    protected EntityManagerInterface $em;

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
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Unused option')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('file');
        if ($arg1) {
            $io->note(sprintf('Processing file: %s', $arg1));
        }

        $importer = new ImporterIB($this->em);
        $records = $importer->start($arg1);
        $portfolio = null;

        // Don't put this line in foreach loop or it will break! :o
        $io->progressStart(iterator_count($records));
        foreach ($records as $offset => $record) {
//        	printf("processing %d: %s,%s,%s\n", $offset, $record[0], $record[1], $record[2]);
            $portfolio = $importer->processOneRecord($portfolio, $record);
            $io->progressAdvance();
        }
        $io->progressFinish();
        $io->success($importer->getReportType() . ' report loaded!');
        return 0;
    }

}
