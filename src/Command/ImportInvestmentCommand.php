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
use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Helper\Balance;
use App\Helper\ImportHelper;
use App\Repository\TransactionRepository;
use App\Values\Payment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import d'un fichier CSV contenant les données de valorisation des placements.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportInvestmentCommand extends Command
{
    protected static $defaultName = 'app:import:investment';
    protected static $defaultDescription = 'Import les données des placements provenant du Calc';

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
     * Classe d'aide pour l'import.
     *
     * @var ImportHelper
     */
    protected $helper;

    /**
     * Ligne de sortie des alertes.
     *
     * @var array<mixed>
     */
    protected $outRows;

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
            ->addArgument('csv', InputArgument::REQUIRED, 'Fichier CSV de HomeBank')
            ->setHelp('Import les données des placements provenant de LibreOffice Calc'."\n"
                .'Le format du fichier CSV doit être au format suivant : '."\n"
                .'account;date;null;info;achat;montant;invest;valorisation;pourcent;month')
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
        $this->outRows = [];

        $this->fileCSV = $input->getArgument('csv');

        $this->inOut->title($this->getDefaultDescription());
        $this->inOut->note(sprintf('Utilisation du fichier : %s', $this->fileCSV));

        // Chargement de l'aide pour gérer les associations
        $this->helper = new ImportHelper($this->entityManager);
        $this->helper->loadAssociations();

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
        if (!is_readable($this->fileCSV)) {
            $this->inOut->error(sprintf('Le fichier %s n\'est pas accessible', $this->fileCSV));

            return Command::FAILURE;
        }

        // Ouverture du fichier d'import
        $file = new SplFileObject($this->fileCSV);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(',');

        if ($this->isImported($file)) {
            $this->inOut->warning(sprintf('Ce fichier %s a été déjà importé', $this->fileCSV));

            return Command::FAILURE;
        }

        $numberLine = $numberFound = $numberAlert = 0;
        $currentAccount = '';
        $lastValorisation = 0.0;
        $listAccount = [];

        $this->inOut->progressStart(count((array) file($this->fileCSV)));

        /**
         * Parcourt chaque ligne du fichier.
         */
        foreach ($file as $row) {
            // $this->inOut->progressAdvance();

            // Ignore end of file
            if ($file->eof()) {
                break;
            }
            ++$numberLine;

            // Récupération de ligne au format Array
            $item = $this->getArrayItem((array) $row, $file->key());

            // Recherche la transaction associé lors d'un versement
            if ('Achat' === $item['action']) {
                $res = $this->checkVersement($item);
                $numberAlert += $res;
                ++$numberFound;
            }

            // On change de placement
            if ($currentAccount !== $item['account']) {
                $currentAccount = $item['account'];
                $listAccount[] = $this->helper->getAccount($item['account']);
                $lastValorisation = 0.0;
            }
            $this->createValorisation($item, $lastValorisation);
            $lastValorisation = $this->helper->getAmount($item['valorisation']);
        }
        $this->entityManager->flush();
        $this->inOut->progressFinish();

        $this->inOut->table(['Date', 'Compte', 'Montant', 'Placement / Error'], $this->outRows);

        $this->inOut->writeln(sprintf('Nombre total dans le fichier      : <info>%s</info>', $numberLine));
        $this->inOut->writeln(sprintf('Nombre de versement               : <info>%s</info>', $numberFound));
        $this->inOut->writeln(sprintf('Nombre de transaction avec alerte : <error>%s</error>', $numberAlert));

        // Calcul des soldes
        $this->outRows = [];
        /** @var Account $account */
        foreach ($listAccount as $account) {
            $this->balance->updateBalanceAll($account);
            $this->outRows[] = [$account->getFullName(), number_format($account->getBalance(), 2, '.', ' '), number_format($account->getInvested(), 2, '.', ' ')];
        }
        $this->inOut->writeln('');
        $this->inOut->table(['Compte', 'Solde', 'Montant investi'], $this->outRows);

        return Command::SUCCESS;
    }

    /**
     * Retourne la ligne du fichier au format Array.
     *
     * @param array<string>|null $line
     * @param int                $numLine
     *
     * @return array<string>
     */
    private function getArrayItem(?array $line, int $numLine): array
    {
        if (10 !== count($line)) {
            $this->inOut->error(sprintf('La ligne %s ne contient pas les 10 colonnes', $numLine));
        }

        [$account, $date, $code, $info, $action, $amount, $total, $valorisation, $pourcent, $month] = $line;
        unset($code,$info,$total,$pourcent);

        return [
            'account' => $account,
            'date' => $date,
            'action' => $action,
            'amount' => $amount,
            'valorisation' => $valorisation,
            'month' => $month,
        ];
    }

    /**
     * Vérifie si l'opération de versement existe sinon l'a créé ou modifie le montant réel.
     *
     * @param array<mixed> $item
     *
     * @return bool
     */
    public function checkVersement(array $item): bool
    {
        $alert = false;
        $result = $this->findTransaction($item);
        if (0 === count($result)) {
            // On créer la transaction de versement
            $this->outRows[] = $this->getOutRow($item, 'Aucune opération trouvé -> Création de la transaction');
            $alert = true;
            $this->helper->createTransaction(
                $item['account'],
                $this->getDate($item['date'], 'm/d/Y'),
                $this->helper->getAmount($item['amount']),
                Recipient::VIRT_NAME,
                Category::getBaseCategoryLabel('VERS+'),
                '',
                0,
                new Payment(Payment::DEPOT)
            );
        } else {
            $amount = 0.0;
            foreach ($result as $transaction) {
                $amount += $transaction->getAmount();
            }
            if ($amount !== $this->helper->getAmount($item['amount'])) {
                $this->outRows[] = $this->getOutRow($item, sprintf('Montant trouvé différent : %s -> Modification du montant à %s', $amount, $this->helper->getAmount($item['amount'])));
                $alert = true;
                // Modifie avec le vrai montant de versement réel
                $result[0]->setAmount($this->helper->getAmount($item['amount']));
            }
        }

        return $alert;
    }

    /**
     * Créer la transaction de valorisation du placement.
     *
     * @param array<mixed> $item
     * @param float        $lastValorisation
     */
    private function createValorisation(array $item, float $lastValorisation): void
    {
        // Calcul de la différence de valorisation par rapprt au mois dernier
        $valorisation = $this->helper->getAmount($item['valorisation']);
        $invested = $this->helper->getAmount($item['amount']);
        $gab = $valorisation - $lastValorisation - $invested;

        if (0.0 === $gab) {
            return;
        }

        $this->helper->createTransaction(
            $item['account'],
            $this->getLasDayOfMonth($item['month']),
            $gab,
            Recipient::VIRT_NAME,
            ($gab > 0) ? Category::getBaseCategoryLabel('EVAL+') : Category::getBaseCategoryLabel('EVAL-'),
            '',
            0,
            new Payment(Payment::INTERNAL)
        );
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
        $item = $this->getArrayItem((array) $file->current(), $file->key());
        $lastValorisation = $this->helper->getAmount($item['valorisation']);
        $file->next();
        $item = $this->getArrayItem((array) $file->current(), $file->key());
        $currentValorisation = $this->helper->getAmount($item['valorisation']);
        $gab = $currentValorisation - $lastValorisation - $this->helper->getAmount($item['amount']);

        /** @var TransactionRepository $repository */
        $repository = $this->entityManager->getRepository(Transaction::class);
        $transaction = $repository->findOneBy([
            'date' => $this->getLasDayOfMonth($item['month']),
            'account' => $this->helper->getAccount($item['account']),
            'amount' => $gab,
        ]);

        return null !== $transaction;
    }

    /**
     * Cherche la transaction correspondante à la ligne du fichier.
     *
     * @param array<string> $item
     *
     * @return Transaction[]
     */
    private function findTransaction(array $item): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('trt')
            ->from(Transaction::class, 'trt')
            ->innerJoin('trt.category', 'cat')
            ->andWhere('trt.date = :date')
            ->andWhere('trt.account = :account')
            ->andWhere('cat.code = :cat')
            ->setParameter('date', $this->getDate($item['date']))
            ->setParameter('account', $this->helper->getAccount($item['account']))
            ->setParameter('cat', Category::VERSEMENT)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne la ligne d'affichage du problème trouvé.
     *
     * @param array<string> $item
     * @param string        $message
     *
     * @return array<mixed>
     */
    private function getOutRow(array $item, string $message): array
    {
        return [
            $this->getDate($item['date'], 'd/m/Y'),
            $item['account'],
            number_format($this->helper->getAmount($item['amount']), 2, '.', ' '),
            $message,
        ];
    }

    /**
     * Retourne la date au format Y-m-d.
     *
     * @param string $date
     * @param string $format Format de sortie
     *
     * @return string
     */
    private function getDate(string $date, string $format = 'Y-m-d'): string
    {
        $objDate = DateTime::createFromFormat('d/m/Y', $date);
        if (false === $objDate) {
            $objDate = new DateTime('1970-01-01');
        }

        return $objDate->format($format);
    }

    /**
     * Retourne le dernier jour dd'une date.
     *
     * @param string $date
     *
     * @return DateTime
     */
    private function getLasDayOfMonth(string $date): DateTime
    {
        $now = new DateTime($this->getDate($date));

        return $now->modify('last day of this month');
    }
}
