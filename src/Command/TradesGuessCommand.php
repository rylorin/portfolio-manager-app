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
use App\Entity\TradeUnit;

class TradesGuessCommand extends Command
{
  protected static $defaultName = 'app:trades:guess';
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
      ->setDescription('Guess trades from account statements')
      ->addArgument('account', InputArgument::REQUIRED, 'Account to crawl')
      ->addOption('option1', null, InputOption::VALUE_NONE, 'Unused option')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $portfolio = null;
    $io = new SymfonyStyle($input, $output);
    $arg1 = $input->getArgument('account');

    if ($arg1) {
      $io->note(sprintf('Rebuilding trades for account: %s', $arg1));
      $portfolio = $this->em->getRepository('App:Portfolio')->findOneBy(['account' => $arg1]);
    }

    if ($input->getOption('option1')) {
      // ...
    }

    if ($portfolio) {
      $statements = $this->em->getRepository('App:Statement')
        ->findByDate(null, null, ['q.portfolio' => $portfolio], ['q.stock' => 'ASC', 'q.date' => 'ASC']);
      $io->progressStart(sizeof($statements));
      $stock = null;
      $tu = null;
      foreach ($statements as $statement) {
        printf(
          "%s %s %s %s\n",
          $statement->getDate()->format('Y-m-d H:i:s'),
          $statement->getStatementType(),
          $statement->getStock(),
          $statement->getDescription()
        );
        if (!$stock || ($stock->getId() != $statement->getStock()->getId())) {
          $stock = $statement->getStock();
          $tu = null;
        }
        if (!$tu) {
          $tu = $statement->getTradeUnit();
        }
        if ($stock) { // if we have a symbol related statement
          print('we have a stock');
          if (!$tu) { // if we don't have a current trade unit yet
            print('we dont have a TU');
            if (substr($statement->getDescription(), 0, 4) == 'Open') { // if we have a position opening statement we can create a new trade unit
              $tu = new TradeUnit();
              $tu->setPortfolio($statement->getPortfolio());
              $tu->setOpeningDate($statement->getDate());
              $tu->setStatus(TradeUnit::OPEN_STATUS);
              $tu->setSymbol($stock);
              $tu->addOpeningTrade($statement);
              if ($statement->getStatementType() == 'Trade') {
                if ($statement->getQuantity() > 0) {
                  $tu->setStrategy(TradeUnit::LONG_STOCK);
                } else {
                  $tu->setStrategy(TradeUnit::SHORT_STOCK);
                }
              } elseif ($statement->getStatementType() == 'TradeOption') {
                $contract = $statement->getContract();
                if ($statement->getQuantity() > 0) {
                  if ($contract->getCallOrPut() == 'C') {
                    $tu->setStrategy(TradeUnit::LONG_CALL);
                  } else {
                    $tu->setStrategy(TradeUnit::LONG_PUT);
                  }
                } else {
                  if ($contract->getCallOrPut() == 'C') {
                    $tu->setStrategy(TradeUnit::SHORT_CALL);
                  } else {
                    $tu->setStrategy(TradeUnit::SHORT_PUT);
                  }
                }
              }
              $statement->setTradeUnit($tu);
              $this->em->persist($tu);
              $this->em->flush();
            } else {
              // nothing possible, we may report a warning
            }
          } else { // fillup an existing trade unit
            print('we have a TU');
          }
        } else {
          print('we dont have a stock');
        }
        $io->progressAdvance();
      }
      $io->progressFinish();
      $io->success('done!');
    } else {
      $io->error('Unknow account: ' . $arg1);
    }
    return 0;
  }

}