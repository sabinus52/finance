<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Category;
use App\Entity\Recipient;
use App\Helper\ImportHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Purge toutes les données de la base avant un import.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class TruncateCommand extends Command
{
    protected static $defaultName = 'app:import:truncate';
    protected static $defaultDescription = 'Purge toutes les données de la base avant un import';

    /**
     * Liste des tables à vider.
     *
     * @var array<string>
     */
    protected static $tables = [
        'stock_portfolio',
        'transaction',
        'recipient',
        'category',
        'account',
        'institution',
    ];

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

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

    protected function configure(): void
    {
        $this
            ->setHelp('Cela va tronquer et effacer toutes les données de la base : '."\n"
                .'  - Organismes'."\n"
                .'  - Comptes'."\n"
                .'  - Catégories'."\n"
                .'  - Bénéficiaires'."\n"
                .'  - Opérations'."\n"
                ."\n".'<error>Cette commandes est IRREVERSIBLE !!!</error>'."\n")
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
        $inOut = new SymfonyStyle($input, $output);
        $rows = [];

        $inOut->title($this->getDefaultDescription());
        $inOut->caution('!!! ATTENTION !!! Toutes les données de la base vont être supprimés.');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Voulez vous continuer [y/N] ? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $inOut->newLine();

            return Command::SUCCESS;
        }

        /**
         * Purge les tables.
         */
        foreach (self::$tables as $table) {
            $return = $this->truncate($table);
            if ('' === $return) {
                $rows[] = ['Vidage de la table '.$table, '<info>OK</info>'];
            } else {
                $rows[] = ['Vidage de la table '.$table, '<error>ERROR</error>', $return];
            }
        }

        /**
         * Creation des éléments obligatoires pour les virements internes.
         */
        $helper = new ImportHelper($this->entityManager);

        $helper->createRecipient(Recipient::VIRT_NAME);
        $rows[] = ['Création du bénéficiaire de virement interne', '<info>OK</info>'];

        foreach (Category::$baseCategories as $cat) {
            $type = ($cat['type']) ? '+' : '-';
            $category = $helper->createCategory($type.$cat['label']);
            $category->setCode($cat['code']);
            $rows[] = [sprintf('Création de la categorie %s', $cat['label']), '<info>OK</info>'];
        }

        $this->entityManager->flush();

        $inOut->newLine();
        $inOut->table(['Action', 'State', 'Error'], $rows);

        return Command::SUCCESS;
    }

    /**
     * Purge la table.
     *
     * @param string $table
     */
    protected function truncate(string $table): string
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        try {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
            $connection->executeStatement($platform->getTruncateTableSQL($table, true /* whether to cascade */));
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return '';
    }
}
