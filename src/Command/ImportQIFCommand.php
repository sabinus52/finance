<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Import\Helper;
use App\Import\QifParser;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import d'un fichier complet CSV ou QIF.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ImportQIFCommand extends Command
{
    protected static $defaultName = 'app:importqif';
    protected static $defaultDescription = 'Import des opérations et comptes au format QIF';

    /**
     * Liste des tables à vider avant import.
     *
     * @var array<string>
     */
    protected static $tables = [
        'stock_portfolio',
        'transaction',
    ];

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var SymfonyStyle
     */
    protected $inOut;

    /**
     * @var array<string>
     */
    protected $filesQIF;

    /**
     * Classe d'aide pour l'import.
     *
     * @var Helper
     */
    protected $helper;

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
            ->addArgument('qif', InputArgument::IS_ARRAY, 'Fichier QIF à importer')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force l\'import sans avertissement')
            ->addOption('parse-memo', null, InputOption::VALUE_NONE, 'Parse les données du champs mémo')
            ->setHelp('Import des opérations et comptes au format QIF')
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

        $this->filesQIF = $input->getArgument('qif');

        $this->inOut->title($this->getDefaultDescription());

        // Chargement de l'aide pour gérer les associations
        $this->helper = new Helper($this->entityManager);
        $this->helper->statistic->setStyle($this->inOut);
        $this->helper->assocDatas->load();
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
        // Message d'avertissement avant suppression des transactions
        if (false === $input->getOption('force')) {
            $this->inOut->caution('!!! ATTENTION !!! Toutes les données de la base vont être supprimés.');
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Voulez vous continuer [y/N] ? ', false);
            if (!$helper->ask($input, $output, $question)) {
                $this->inOut->newLine();

                return Command::SUCCESS;
            }
        }

        // Vide les tables de transactions avant
        $this->truncateTransactions();

        // Parse chaque fichier QIF
        foreach ($this->filesQIF as $fileQIF) {
            $this->inOut->note(sprintf('Utilisation et parsing du fichier : %s', $fileQIF));

            // Test si accessible
            if (!is_readable($fileQIF)) {
                $this->inOut->error(sprintf('Le fichier %s n\'est pas accessible', $fileQIF));

                return Command::FAILURE;
            }

            // Ouverture du parseur du fichier
            $file = new SplFileObject($fileQIF);
            $parser = new QifParser($file, $this->helper, $input->getOption('parse-memo'));

            // Parse le fichier QIF
            $parser->parse();
            $this->entityManager->flush();

            // Fait les associations entre les transactions des virements internes
            $parser->setAssocTransfer();
            $this->entityManager->flush();
        }

        // Calcul des soldes
        $this->helper->calulateBalance();

        // Affiche les rapports
        $this->inOut->section('Résultat de l\'import');
        $this->inOut->text('Nouveaux éléments trouvées et crées :');
        $this->inOut->table(['Nouvel élément trouvé et créé', 'type'], $this->helper->assocDatas->getReportNewCreated());
        $this->inOut->text('Avertissements :');
        $this->helper->statistic->reportMemoAlerts();
        $this->inOut->text('Récapitulatifs des comptes importés :');
        $this->helper->statistic->reportAccounts();
        $this->inOut->text('Catégories trouvées et importées :');
        $this->helper->statistic->reportCategories();

        return Command::SUCCESS;
    }

    /**
     * Vide les tables de transactions.
     *
     * @return bool
     */
    protected function truncateTransactions(): bool
    {
        $rows = [];
        $isError = false;

        foreach (self::$tables as $table) {
            $return = $this->helper->truncate($table);
            if (null === $return) {
                $rows[] = ['Vidage de la table '.$table, '<info>OK</info>'];
            } else {
                $rows[] = ['Vidage de la table '.$table, '<error>ERROR</error>', $return];
                $isError = true;
            }
        }

        $this->inOut->newLine();
        $this->inOut->table(['Action', 'State', 'Error'], $rows);

        return $isError;
    }
}
