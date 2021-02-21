<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use OfxParser\Parsers\Investment;
// You'll probably want to alias the namespace:
use OfxParser\Entities\Investment as InvEntities;
use App\Importer\ImporterOFX;

class ImportOfxCommand extends Command
{
    protected static $defaultName = 'app:import:ofx';
    protected EntityManagerInterface $em;

    /**
     * constructor.
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
            ->setDescription('Add a short description for your command')
            ->addArgument('file', InputArgument::REQUIRED, 'OFX file to import')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $arg1 = $input->getArgument('file');
        $io->note(sprintf('Processing file: %s', $arg1));

        $importer = new ImporterOFX($this->em);
        $importer->start($arg1);

        $portfolio = $importer->getPortfolio();
        // Loop over transactions
        foreach ($importer->getTransactions() as $ofxEntity) {
            $importer->processOneTransaction($portfolio, $ofxEntity);
        }

        $io->success(sprintf('Ended processing account: %s', $importer->getAccountNumber()));

        return 0;
    }
}
