<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Model;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class ImportModel extends Command
{
    protected static $defaultDescription = '';

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
    protected $fileCSV;

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
     * Initialise la commande.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->inOut = new SymfonyStyle($input, $output);

        $this->fileCSV = $input->getArgument('csv');

        $this->inOut->title($this->getDefaultDescription());
        $this->inOut->note(sprintf('Utilisation du fichier CSV : %s', $this->fileCSV));
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
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Cela va supprimer toutes les données déjà présentes !'."\n".'Voulez vous continuer [y/N] ? ', false);
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        if (!$this->testFile()) {
            return Command::FAILURE;
        }

        if (!$this->purge()) {
            return Command::FAILURE;
        }

        $numberLines = $this->parse();

        $this->inOut->success(sprintf('Import effectué avec succès, %s occurrences ont été importés', $numberLines));

        return Command::SUCCESS;
    }

    /**
     * Purge les données avant l'import.
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @return bool
     */
    protected function purge(): bool
    {
        throw new LogicException('You must override the purge() method in the concrete command class.');
    }

    /**
     * Parse le fichier et import les données et retoune le nombre d'occurrences importées.
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @return int
     */
    protected function parse(): int
    {
        throw new LogicException('You must override the parse() method in the concrete command class.');
    }

    /**
     * Purge la table.
     *
     * @param string $table
     */
    protected function truncate(string $table): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL($table, false /* whether to cascade */));
    }

    /**
     * Test si le fichier est accessible.
     *
     * @return bool
     */
    protected function testFile(): bool
    {
        if (!file_exists($this->fileCSV) || !is_readable($this->fileCSV)) {
            $this->inOut->error(sprintf('Le fichier %s n\'est pas accessible', $this->fileCSV));

            return false;
        }

        return true;
    }
}
