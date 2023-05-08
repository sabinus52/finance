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
use App\WorkFlow\Balance;
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

        $this->calculateBalance();

        return Command::SUCCESS;
    }

    /**
     * Calcule les soldes de chaque comptes.
     */
    private function calculateBalance(): void
    {
        $helper = new Balance($this->entityManager);
        $accounts = $this->entityManager->getRepository(Account::class)->findAll();

        $this->inOut->section('Calcul des soldes');
        $this->inOut->progressStart(count($accounts));

        foreach ($accounts as $account) {
            $this->inOut->progressAdvance();
            $helper->updateBalanceAll($account);
        }

        $this->inOut->progressFinish();
    }
}
