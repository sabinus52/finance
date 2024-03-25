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
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Commande d'initialisation de l'application.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[\Symfony\Component\Console\Attribute\AsCommand('app:initialize', "Initialisation de l'application")]
class InitializeApplication extends Command
{
    protected const CSV_CATEGORIES = 'datas/categories.csv';

    /**
     * @var SymfonyStyle
     */
    protected $inOut;

    /**
     * Constructeur.
     */
    public function __construct(
        protected KernelInterface $kernel,
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    /**
     * Configuration de la commande.
     */
    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, "Force l'initialisation sans avertissement")
            ->addOption('with-categories', 'a', InputOption::VALUE_NONE, 'Charge toutes les catégories')
            ->setHelp('Initialisation de l\'application avec effacement des données actuelles')
        ;
    }

    /**
     * Initialise la commande.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->inOut = new SymfonyStyle($input, $output);

        $this->inOut->title($this->getDefaultDescription());
    }

    /**
     * Execute la commande.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Message d'avertissement avant suppression de la BDD
        if (false === $input->getOption('force')) {
            $this->inOut->caution('!!! ATTENTION !!! Toutes les données de la base vont être supprimés.');
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Voulez vous continuer [y/N] ? ', false);
            if (!$helper->ask($input, $output, $question)) {
                $this->inOut->newLine();

                return Command::SUCCESS;
            }
        }

        // Suppression du shema de la BDD
        $command = $this->getApplication()->find('doctrine:schema:drop');
        if (Command::SUCCESS !== $command->run(new ArrayInput(['--force' => true]), $output)) {
            return Command::FAILURE;
        }

        // Creation du shema de la BDD
        $command = $this->getApplication()->find('doctrine:schema:create');
        if (Command::SUCCESS !== $command->run(new ArrayInput([]), $output)) {
            return Command::FAILURE;
        }

        // Chargement des bénéficiaires
        $number = $this->loadRecipients();
        $this->inOut->writeln('Chargement des bénéficiares                            : '.$number);

        // Chargement des catégories de mouvements internes
        $number = $this->loadMovements();
        $this->inOut->writeln('Chargement des catégories de mouvements internes       : '.$number);

        // Chargement des catégories supplémentaires
        if (true === $input->getOption('with-categories')) {
            $number = $this->loadCategories();
            $this->inOut->writeln('Chargement des catégories personnalisées               : '.$number);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    /**
     * Chargement des bénéficiaires.
     */
    private function loadRecipients(): int
    {
        $recipient = new Recipient();
        $recipient->setName(Recipient::VIRT_NAME);

        $this->entityManager->persist($recipient);

        return 1;
    }

    /**
     * Chargement des catégories des mouvements bancaires (OBLIGATOIRE).
     */
    private function loadMovements(): int
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $mvtLevel1 = [];
        $number = 0;

        // Catégories de niveau 1
        foreach (Category::$movementsLevel1 as $type => $label) {
            $mvtLevel1[(bool) $type] = $repository->create((bool) $type, $label, null, Category::MOVEMENT);
        }

        // Catégories de niveau 2
        foreach (Category::$movements as $code => $cat) {
            $repository->create(false, $cat[0], $mvtLevel1[false], $code);
            $repository->create(true, $cat[1], $mvtLevel1[true], $code);
            $number += 2;
        }

        return $number;
    }

    /**
     * Chargement des catégories.
     */
    private function loadCategories(): int
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $catLevel1 = null;
        $number = 0;

        $fileCSV = new \SplFileObject($this->kernel->getProjectDir().'/'.self::CSV_CATEGORIES);
        $fileCSV->setFlags(\SplFileObject::READ_CSV);
        $fileCSV->setCsvControl(';');

        /** @var array<string> $line */
        foreach ($fileCSV as $line) {
            if ('1' === $line[0]) {
                $catLevel1 = $repository->create($this->isTypeCategory($line[1]), $line[2]);
            }
            if ('2' === $line[0]) {
                $code = empty($line[3]) ? null : $line[3];
                $repository->create($this->isTypeCategory($line[1]), $line[2], $catLevel1, $code);
                ++$number;
            }
        }

        return $number;
    }

    /**
     * Retourne si dépenses ou recettes.
     */
    private function isTypeCategory(string $type): bool
    {
        if ('+' === $type) {
            return Category::RECETTES;
        }
        if ('-' === $type) {
            return Category::DEPENSES;
        }
        throw new \Exception('Type de la catégorie inconnu (+/-)');
    }
}
