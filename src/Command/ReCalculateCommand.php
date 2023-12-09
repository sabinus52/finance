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
use App\WorkFlow\WalletHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
     * Configuration de la commande.
     */
    protected function configure(): void
    {
        $this
            ->addOption('year', null, InputOption::VALUE_IS_ARRAY + InputOption::VALUE_OPTIONAL, 'Affiche les années de l\'historique des portefeuilles')
            ->setHelp('Recalcule les soldes de tous les comptes bancaires et portefeuille boursier')
        ;
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

        $this->calculateBalance();
        $this->printAccounts();
        $this->printWallets();

        if (!empty($input->getOption('year'))) {
            $this->debugWallet($input->getOption('year'));
        }

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
            $helper->updateBalanceFromScratch($account);
        }

        $this->inOut->progressFinish();
    }

    /**
     * Affiche les comptes.
     */
    private function printAccounts(): void
    {
        $output = [];
        foreach ($this->accounts as $account) {
            $balance = round($account->getBalance()->getBalance(), 2);
            $recon = round($account->getBalance()->getReconBalance(), 2);
            $investment = round($account->getBalance()->getInvestment(), 2);
            $repurchase = round($account->getBalance()->getRepurchase(), 2);
            $output[] = [
                $account,
                ($balance <= 0) ? '' : $balance.' €',
                ($recon <= 0) ? '' : $recon.' €',
                ($investment <= 0) ? '' : $investment.' €',
                ($repurchase <= 0) ? '' : $repurchase.' €',
            ];
        }
        $this->inOut->table(['Compte', 'Solde', 'Rapprochement', 'Investissement', 'Rachat'], $output);
    }

    /**
     * Affiche les portefeuilles.
     */
    private function printWallets(): void
    {
        foreach ($this->accounts as $account) {
            if (AccountType::EPARGNE_FINANCIERE !== $account->getTypeCode()) {
                continue;
            }

            $result = $this->entityManager->getRepository(StockWallet::class)->findBy(['account' => $account]);
            $wallet = new WalletHistory();
            $wallet->setWallet($result);
            $this->printWallet($account, $wallet);
        }
    }

    /**
     * Affiche le portefeuille.
     *
     * @param Account       $account
     * @param WalletHistory $wallet
     */
    private function printWallet(Account $account, WalletHistory $wallet): void
    {
        $this->inOut->writeln(sprintf('%s (%s)', $account->getFullName(), $wallet->getDate()->format('Y-m')));
        $output = [];
        /** @var StockWallet $item */
        foreach ($wallet as $item) {
            $output[] = [
                $item->getStock(),
                $item->getVolume(),
                $item->getPrice().' €',
                ($item->getPriceDate()) ? $item->getPriceDate()->format('d/m/Y') : '',
                $item->getInvest().' €',
                $item->getDividend(),
                $item->getFee(),
            ];
        }
        $this->inOut->table(['Stock', 'Volume', 'Cours', 'Date', 'Invest', 'Dividendes', 'Commission'], $output);
    }

    /**
     * Construction des portefeuilles boursiers.
     *
     * @param array<string> $years
     */
    private function debugWallet(array $years): void
    {
        $this->inOut->section('Calcul des portefeuilles boursiers');

        $doctrine = new DoctrineHelper($this->entityManager);
        $doctrine->truncate(StockWallet::class);

        foreach ($this->accounts as $account) {
            if (AccountType::EPARGNE_FINANCIERE !== $account->getTypeCode()) {
                continue;
            }

            $helper = new Wallet($this->entityManager, $account);
            $results = $helper->buidAndSaveWallet();

            // Affiche les portefeuilles désirés avec l'option "year"
            foreach ($results as $month => $wallet) {
                $year = substr($month, 0, 4);
                if (in_array($year, $years, true) || in_array('all', $years, true)) {
                    $this->printWallet($account, $wallet);
                }
            }
        }
    }
}
