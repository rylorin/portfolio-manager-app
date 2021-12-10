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
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use GuzzleHttp\Client;

/*
 * https://github.com/scheb/yahoo-finance-api
 */
class AppExpireCommand extends Command
{
    protected static $defaultName = 'app:expire';

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
            ->setDescription('Remove expired options contracts or closed positions')
            ->addOption('options', null, InputOption::VALUE_NONE, 'Remove expired options contracts')
            ->addOption('positions', null, InputOption::VALUE_NONE, 'Remove closed positions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('options')) {
            $now = (new \DateTime())->sub(new \DateInterval('P1D'));
            $options = $this->em->getRepository('App:Option')->findByBeforeLastTradeDate($now);
            $io->progressStart(sizeof($options));
            // Expire options
            foreach ($options as $contract) {
                $positions = $contract->getPositions();
                $statements = $this->em->getRepository('App:OptionTradeStatement')->findByContract($contract);
                if (!sizeof($positions) && !sizeof($statements)) {
//                  $io->note(sprintf("Expiring option %s\n", $contract->getSymbol()));
                    $this->em->remove($contract);
                } else {
//                    printf("%d positions, %d statements\n", sizeof($positions), sizeof($statements));
                    $contract->setDelta(null);
                    $contract->setPrice(null);
                    $contract->setUpdated(null);
                    $contract->setBid(null);
                    $contract->setBidDate(null);
                    $contract->setAsk(null);
                    $contract->setAskDate(null);
                }
                $io->progressAdvance();
            }
            // save / write the changes to the database
            $this->em->flush();
            $io->progressFinish();
            $io->success('Options contracts expired.');
        }

        if ($input->getOption('positions')) {
            $data = $this->em->getRepository('App:Position')->findBySecType(
                null,
                [ 'p.quantity' => 0 ]
            );
            $io->progressStart(sizeof($data));
            foreach ($data as $position) {
                $position->getPortfolio()->removePosition($position);
                $position->getContract()->removePosition($position);
                $io->progressAdvance();
            }
            // save / write the changes to the database
            $this->em->flush();
            $io->progressFinish();
            $io->success('Closed positions removed.');
        }

        return 0;
    }

}
