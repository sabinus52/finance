<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Model;
use App\Transaction\TransactionModelRouter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Commande des créations des transactions planifiées.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class ScheduleCommand extends Command
{
    protected static $defaultName = 'app:schedule';

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var SymfonyStyle
     */
    protected $inOut;

    /**
     * Router des transactions.
     *
     * @var TransactionModelRouter
     */
    private $router;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    /**
     * Configuration de la commande.
     */
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode simulation')
            ->setDescription('Création des transactions planifiées du mois en cours')
        ;
    }

    /**
     * Initialisation de la commande.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->inOut = new SymfonyStyle($input, $output);
        $this->router = new TransactionModelRouter($this->entityManager);
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
        $this->inOut->title($this->getDescription());
        $output = [];

        /** @var Model[] $schedules */
        $schedules = $this->entityManager->getRepository(Model::class)->findScheduleToDo(); /** @phpstan-ignore-line */
        foreach ($schedules as $schedule) {
            $output[] = [
                $schedule->getSchedule()->getDoAt()->format('d/m/Y'),
                $schedule->getAccount(),
                ($schedule->getTransfer()) ? '>> '.$schedule->getTransfer() : $schedule->getRecipient(),
                $schedule->getCategory(),
                number_format($schedule->getAmount(), 2, '.', ' ').' €',
            ];

            if (!$input->getOption('dry-run')) {
                $this->createTransaction($schedule);
            }
        }

        if (!$input->getOption('quiet')) {
            $this->inOut->table(['Date', 'Compte', 'Tiers', 'Catégorie', 'Montant'], $output);
        }

        return Command::SUCCESS;
    }

    /**
     * Création de la transaction à partir du modèle.
     */
    private function createTransaction(Model $model): void
    {
        // Création de la transaction
        $modelTransaction = $this->router->createFromModel($model);
        $form = $this->formFactory->create($modelTransaction->getFormClass(), $modelTransaction->getTransaction(), $modelTransaction->getFormOptions() + ['isNew' => true]);
        if ($modelTransaction->isTransfer()) {
            $form->get('source')->setData($model->getAccount());
            $form->get('target')->setData($model->getTransfer());
        }
        $modelTransaction->insert($form);

        // Prochaine planification
        $schedule = $model->getSchedule();
        $schedule->setNextDoAt();
        $this->entityManager->flush();
    }
}
