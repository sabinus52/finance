<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Transaction;
use App\Helper\ImportHelper;
use App\Helper\QifParser;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import d'un fichier complet CSV ou QIF.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
     * @var ImportHelper
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
            ->addArgument('qif', InputArgument::REQUIRED, 'Fichier QIF à importer')
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
        $this->helper = new ImportHelper($this->entityManager);
        $this->helper->statistic->setStyle($this->inOut);
        $this->helper->loadAssociations();
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

        $file = new SplFileObject($this->fileQIF);
        $parser = new QifParser($file, $this->helper);

        if ($this->isImported($file)) {
            $this->inOut->warning(sprintf('Ce fichier %s a été déjà importé', $this->fileQIF));

            return Command::FAILURE;
        }

        // Parse le fichier QIF
        $parser->parse();
        $this->entityManager->flush();

        // Fait les associations entre les transactions des virements internes
        $parser->setAssocTransfer();
        $parser->setAssocInvestment();
        $this->entityManager->flush();

        // Affiche les rapports
        $this->helper->statistic->reportAccounts();
        $this->helper->statistic->reportCategories();

        return Command::SUCCESS;
    }

    /**
     * Test si le fichier a été déjà importé.
     *
     * @param SplFileObject $file
     *
     * @return bool
     */
    private function isImported(SplFileObject $file): bool
    {
        $date = $account = '';
        $amount = 0.0;

        // Recherche les premières valeurs dans le fichier
        foreach ($file as $line) {
            $line = (string) str_replace(["\n", "\r"], ['', ''], $line); /** @phpstan-ignore-line */
            if ('D' === $line[0]) {
                $date = substr($line, 1);
            } elseif ('T' === $line[0]) {
                $amount = $this->helper->getAmount(substr($line, 1));
            } elseif ('N' === $line[0]) {
                $account = substr($line, 1);
            }

            if ('' !== $date && 0.0 !== $amount && '' !== $account) {
                break;
            }
        }

        /** @var TransactionRepository $repository */
        $repository = $this->entityManager->getRepository(Transaction::class);
        $transaction = $repository->findOneBy([
            'date' => $this->helper->getDateTime($date, QifParser::DATE_FORMAT),
            'account' => $this->helper->getAccount($account),
            'amount' => $amount,
        ]);

        return null !== $transaction;
    }
}
