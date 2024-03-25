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
#[\Symfony\Component\Console\Attribute\AsCommand('app:schedule', 'Création des transactions planifiées du mois en cours')]
class ScheduleCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $inOut;

    /**
     * Router des transactions.
     */
    private ?TransactionModelRouter $router = null;

    /**
     * Constructeur.
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected FormFactoryInterface $formFactory
    ) {
        parent::__construct();
    }

    /**
     * Configuration de la commande.
     */
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode simulation')
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
                (null !== $schedule->getTransfer()) ? '>> '.$schedule->getTransfer() : $schedule->getRecipient(),
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
