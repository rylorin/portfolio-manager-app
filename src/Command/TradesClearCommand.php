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

class TradesClearCommand extends Command
{
    protected static $defaultName = 'app:trades:clear';
    protected static $defaultDescription = 'Add a short description for your command';

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
            ->setDescription(self::$defaultDescription)
            ->addArgument('account', InputArgument::REQUIRED, 'Account to clear')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('account');

        if ($arg1) {
          $io->note(sprintf('Clearing trades for account: %s', $arg1));
          $portfolio = $this->em->getRepository('App:Portfolio')->findOneBy([ 'account' => $arg1 ]);
        }

        if ($input->getOption('option1')) {
            // ...
        }

        if ($portfolio) {
          $trades = $this->em->getRepository('App:TradeUnit')->findTradeUnits(
            [ 'q.portfolio' => $portfolio ]
          );
          foreach ($trades as $trade) {
            $this->em->remove($trade);
          }
          $this->em->flush();
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}
