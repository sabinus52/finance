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
use App\Entity\Transaction;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Undocumented class.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class SearchAnomaliesCommand extends Command
{
    protected static $defaultName = 'app:import:anomalies';
    protected static $defaultDescription = 'Recherche les anomalies';

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
     * Configuration de la commande.
     */
    protected function configure(): void
    {
        $this->setHelp('Recherche les anomalies dans les transactions après un import.'."\n");
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

        $this->inOut->title($this->getDefaultDescription());
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
        $this->inOut->section('Rapport des modes de paiement non valides');
        $this->reportPaymentNull();

        $this->inOut->section('Rapport des montants nuls');
        $this->reportAmountNull();

        $this->inOut->section('Rapport des dates invalides');
        $this->reportDateNull();

        $this->inOut->section('Rapport des catégories dépenses avec montant positif');
        $this->reportCatDepenses();

        $this->inOut->section('Rapport des catégorie recettes avec montant négatif');
        $this->reportCatRecettes();

        $this->inOut->section('Rapport des catégorie inconnu');
        $this->reportCatUnknow();

        return Command::SUCCESS;
    }

    /**
     * Affiche les modes de payment erronés.
     */
    public function reportPaymentNull(): void
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->andWhere('trt.payment = 0')
            ->andWhere('trt.transfer IS NULL')
            ->getQuery()
        ;

        $this->findAndPrintAnomlies($query);
    }

    /**
     * Affiche les transactions avec des montants nuls.
     */
    public function reportAmountNull(): void
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->andWhere('trt.amount = 0')
            ->getQuery()
        ;

        $this->findAndPrintAnomlies($query);
    }

    /**
     * Affiche les transactions avec des dates nulles.
     */
    public function reportDateNull(): void
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->andWhere('trt.date = :date')
            ->setParameter('date', new DateTime('1970-01-01'))
            ->getQuery()
        ;

        $this->findAndPrintAnomlies($query);
    }

    /**
     * Affiche les transactions avec des montants positifs pour des catégories de type dépenses.
     */
    public function reportCatDepenses(): void
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.amount > 0')
            ->andWhere('cat.type = :type')
            ->setParameter('type', Category::DEPENSES)
            ->getQuery()
        ;

        $this->findAndPrintAnomlies($query);
    }

    /**
     * Affiche les transactions avec des montants négatifs pour des catégories de type recettes.
     */
    public function reportCatRecettes(): void
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.amount < 0')
            ->andWhere('cat.type = :type')
            ->setParameter('type', Category::RECETTES)
            ->getQuery()
        ;

        $this->findAndPrintAnomlies($query);
    }

    /**
     * Affiche les transactions avec une catégorie INCONNU.
     */
    public function reportCatUnknow(): void
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('cat.parent', 'c1')
            ->andWhere('c1.name = :name')
            ->setParameter('name', 'Inconnu')
            ->getQuery()
        ;

        $this->findAndPrintAnomlies($query);
    }

    /**
     * Retourne la liste des anomalies.
     *
     * @param Query $query
     */
    private function findAndPrintAnomlies(Query $query): void
    {
        $outRows = [];

        /** @var Transaction $transaction */
        foreach ($query->getResult() as $transaction) {
            $outRows[$transaction->getId()] = [
                $transaction->getDate()->format('d/m/Y'),
                $transaction->getAccount()->getFullName(),
                $transaction->getRecipient()->getName(),
                $transaction->getCategory()->getFullName(),
                number_format($transaction->getAmount(), 2, '.', ' '),
            ];
        }

        $this->inOut->table(['Date', 'Compte', 'Tiers', 'Catégorie', 'Montant'], $outRows);
    }
}
