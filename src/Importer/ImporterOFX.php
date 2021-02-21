<?php

declare(strict_types=1);

namespace App\Importer;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Portfolio;

class ImporterOFX
{
    protected EntityManagerInterface $em;
    protected $ofx;

    /**
     * constructor.
     *
     * @param EntityManagerInterface $em
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(EntityManagerInterface $em)
    {
//        parent::__construct();
        $this->em = $em;
    }

    public function start(string $fileName): void {
        // Load the OFX file
        $ofxParser = new \OfxParser\Parsers\Investment();
        $this->ofx = $ofxParser->loadFromFile($fileName);
    }

    public function getTransactions() {
        return $this->ofx->bankAccounts[0]->statement->transactions;
    }

    public function getAccountNumber() {
        return $this->ofx->bankAccounts[0]->accountNumber;
    }

    public function getBaseCurrency() {
        return $this->ofx->bankAccounts[0]->statement->currency;
    }

    public function getPortfolio(): Portfolio {
        $portfolio = $this->em->getRepository('App:Portfolio')->findOneBy([ 'account' => $this->getAccountNumber() ]);
        if (!$portfolio) {
            $portfolio = new Portfolio();
            $portfolio->setAccount($this->getAccountNumber());
            $portfolio->setBaseCurrency($this->getBaseCurrency());
            $this->em->persist($portfolio);
        }
        return $portfolio;
    }

    public function processOneTransaction(?Portfolio $portfolio, $ofxEntity): void {
        // Keep in mind... not all properties are inherited for all transaction types...
    //    print_r($ofxEntity);

        // Maybe you'll want to do something based on the entity:
        if ($ofxEntity instanceof \OfxParser\Entities\Investment\Transaction\Banking) {
        } elseif ($ofxEntity instanceof \OfxParser\Entities\Investment\Transaction\Income) {
        } elseif ($ofxEntity instanceof \OfxParser\Entities\Investment\Transaction\BuyStock) {
        } elseif ($ofxEntity instanceof \OfxParser\Entities\Investment\Transaction\SellStock) {
            print_r($ofxEntity);
        } elseif ($ofxEntity instanceof \OfxParser\Entities\Investment\Transaction\SellOption) {
        } elseif ($ofxEntity instanceof \OfxParser\Entities\Investment\Transaction\BuyOption) {
        } elseif ($ofxEntity instanceof \OfxParser\Entities\Investment\Transaction\InvExpense) {
        } else {
            print_r($ofxEntity);
        }

        // Maybe you'll want to do something based on the transaction properties:
        $nodeName = $ofxEntity->nodeName;
        if ($nodeName == 'BUYSTOCK') {
            // @see OfxParser\Entities\Investment\Transaction...

            $amount = abs($ofxEntity->total);
            $cusip = $ofxEntity->securityId;

            // ...
        }
    }

}
