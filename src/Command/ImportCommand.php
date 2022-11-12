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
use App\Entity\Transaction;
use App\Helper\Balance;
use App\Import\Helper;
use App\Import\QifItem;
use App\Import\QifParser;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import d'un fichier complet CSV ou QIF.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportCommand extends Command
{
    protected static $defaultName = 'app:import:import';
    protected static $defaultDescription = 'Import des opérations et comptes au format QIF';

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var SymfonyStyle
     */
    protected $inOut;

    /**
     * @var string
     */
    protected $fileQIF;

    /**
     * Classe d'aide pour l'import.
     *
     * @var Helper
     */
    protected $helper;

    /**
     * Classe de calcul du solde.
     *
     * @var Balance
     */
    protected $balance;

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
            ->addArgument('qif', InputArgument::REQUIRED, 'Fichier QIF à importer')
            ->addOption('parse-memo', null, InputOption::VALUE_NONE, 'Parse les données du champs mémo')
            ->addOption('pea', null, InputOption::VALUE_REQUIRED, 'Indique le compte caisse du PEA')
            ->setHelp('')
        ;
    }

    /**
     * Initialise la commande.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->inOut = new SymfonyStyle($input, $output);

        $this->fileQIF = $input->getArgument('qif');

        $this->inOut->title($this->getDefaultDescription());
        $this->inOut->note(sprintf('Utilisation du fichier : %s', $this->fileQIF));

        // Chargement de l'aide pour gérer les associations
        $this->helper = new Helper($this->entityManager);
        $this->helper->statistic->setStyle($this->inOut);
        $this->helper->assocDatas->load();

        $this->balance = new Balance($this->entityManager);
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
        // Test si accessible
        if (!is_readable($this->fileQIF)) {
            $this->inOut->error(sprintf('Le fichier %s n\'est pas accessible', $this->fileQIF));

            return Command::FAILURE;
        }

        // Ouverture du parseur du fichier
        $file = new SplFileObject($this->fileQIF);
        $options = [
            'parse-memo' => $input->getOption('parse-memo'),
            'pea' => $input->getOption('pea'),
        ];
        $parser = new QifParser($file, $this->helper, $options);

        // Si déjà importé
        if ($this->isImported($file)) {
            $this->inOut->warning(sprintf('Ce fichier %s a été déjà importé', $this->fileQIF));

            return Command::FAILURE;
        }

        // Parse le fichier QIF
        $parser->parse();
        $this->entityManager->flush();

        // Fait les associations entre les transactions des virements internes
        $parser->setAssocTransfer();
        $this->entityManager->flush();

        // Calcul des soldes
        $this->calulateBalance();

        // Affiche les rapports
        $this->helper->statistic->reportAlerts();
        $this->helper->statistic->reportAccounts();
        $this->helper->statistic->reportCategories();

        return Command::SUCCESS;
    }

    /**
     * Calcule le solde de tous les comptes.
     */
    protected function calulateBalance(): void
    {
        /** @var AccountRepository $repository */
        $repository = $this->entityManager->getRepository(Account::class);
        $accounts = $repository->findAll();

        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->balance->updateBalanceAll($account);
            $this->helper->statistic->setAccountData($account);
        }
    }

    /**
     * Test si le fichier a été déjà importé.
     *
     * @param SplFileObject $file
     *
     * @return bool
     */
    protected function isImported(SplFileObject $file): bool
    {
        $date = $account = $amount = '';
        $item = new QifItem($this->helper->assocDatas);

        // Recherche les premières valeurs dans le fichier
        foreach ($file as $line) {
            $line = (string) str_replace(["\n", "\r"], ['', ''], $line); /** @phpstan-ignore-line */
            if ('D' === $line[0]) {
                $date = substr($line, 1);
                $item->setDate($date);
            } elseif ('T' === $line[0]) {
                $amount = substr($line, 1);
                $item->setAmount($amount);
            } elseif ('N' === $line[0]) {
                $account = substr($line, 1);
                $item->setAccount($account);
            }

            if ('' !== $date && 0.0 !== $item->getAmount() && '' !== $account) {
                break;
            }
        }

        /** @var TransactionRepository $repository */
        $repository = $this->entityManager->getRepository(Transaction::class);
        $transaction = $repository->findOneBy([
            'date' => $item->getDate(),
            'account' => $item->getAccount(),
            'amount' => $item->getAmount(),
        ]);

        return null !== $transaction;
    }
}
