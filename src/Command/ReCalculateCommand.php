<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Account;
use App\Entity\StockWallet;
use App\Helper\DoctrineHelper;
use App\Values\AccountType;
use App\WorkFlow\Balance;
use App\WorkFlow\Wallet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande de recalcul des soldes.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ReCalculateCommand extends Command
{
    protected static $defaultName = 'app:recalcul';
    protected static $defaultDescription = 'Recalcul des soldes de tous les comptes';

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var SymfonyStyle
     */
    protected $inOut;

    /**
     * Liste de tous les comptes.
     *
     * @var Account[]
     */
    private $accounts;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * Execute la commande.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->inOut = new SymfonyStyle($input, $output);
        $this->inOut->title($this->getDefaultDescription());

        $this->accounts = $this->entityManager->getRepository(Account::class)->findAll();

        $this->calculateWallet();
        $this->calculateBalance();

        return Command::SUCCESS;
    }

    /**
     * Calcule les soldes de chaque comptes.
     */
    private function calculateBalance(): void
    {
        $helper = new Balance($this->entityManager);

        $this->inOut->section('Calcul des soldes des comptes');
        $this->inOut->progressStart(count($this->accounts));

        foreach ($this->accounts as $account) {
            $this->inOut->progressAdvance();
            $helper->updateBalanceAll($account);
        }

        $this->inOut->progressFinish();
        $this->printAccounts();
    }

    /**
     * Affiche les comptes.
     */
    private function printAccounts(): void
    {
        $output = [];
        foreach ($this->accounts as $account) {
            $balance = round($account->getBalance(), 2);
            $recon = round($account->getReconBalance(), 2);
            $invest = round($account->getInvested(), 2);
            $output[] = [
                $account,
                ($balance <= 0) ? '' : $balance.' €',
                ($recon <= 0) ? '' : $recon.' €',
                ($invest <= 0) ? '' : $invest.' €',
            ];
        }
        $this->inOut->table(['Compte', 'Solde', 'Rapprochement', 'Montant investi'], $output);
    }

    /**
     * Construction des portefeuilles boursiers.
     */
    private function calculateWallet(): void
    {
        $this->inOut->section('Calcul des portefeuilles boursiers');

        $doctrine = new DoctrineHelper($this->entityManager);
        $doctrine->truncate(StockWallet::class);

        foreach ($this->accounts as $account) {
            if (AccountType::EPARGNE_FINANCIERE !== $account->getTypeCode()) {
                continue;
            }

            $helper = new Wallet($this->entityManager, $account);
            $results = $helper->reBuild();
            $this->printWallet($account, $results);
        }
    }

    /**
     * Affiche le portefeuille.
     *
     * @param Account       $account
     * @param StockWallet[] $wallet
     */
    private function printWallet(Account $account, array $wallet): void
    {
        $this->inOut->writeln($account->getFullName());
        $output = [];
        foreach ($wallet as $item) {
            $output[] = [
                $item->getStock(),
                $item->getVolume(),
                $item->getPrice().' €',
            ];
        }
        $this->inOut->table(['Stock', 'Volume', 'Cours'], $output);
    }
}
