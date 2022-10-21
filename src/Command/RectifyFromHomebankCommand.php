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
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Helper\ImportHelper;
use App\Values\Payment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\Date;

/**
 * Import d'un fichier complet CSV ou QIF.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class RectifyFromHomebankCommand extends Command
{
    protected static $defaultName = 'app:import:homebank';
    protected static $defaultDescription = 'Rectifie les données provenant de HomeBank';

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
            ->setHelp('Rectifie les données provenant d\'une extraction au format CSV de HomeBank'."\n"
                .'Le format du fichier CSV doit être au format suivant : '."\n"
                .'account;date;paymode;info;payee;memo;amount;c;category;tags')
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

        $this->fileCSV = $input->getArgument('csv');

        $this->inOut->title($this->getDefaultDescription());
        $this->inOut->note(sprintf('Utilisation du fichier : %s', $this->fileCSV));

        // Chargement de l'aide pour gérer les associations
        $this->helper = new ImportHelper($this->entityManager);
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
        $outRows = [];

        // Test si accessible
        if (!is_readable($this->fileCSV)) {
            $this->inOut->error(sprintf('Le fichier %s n\'est pas accessible', $this->fileCSV));

            return Command::FAILURE;
        }

        // Ouverture du fichier d'import
        $file = new SplFileObject($this->fileCSV);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(';');
        $numberLine = $numberFound = 0;

        $this->inOut->progressStart(count((array) file($this->fileCSV)));

        /**
         * Parcourt chaque ligne du fichier.
         */
        foreach ($file as $key => $row) {
            $this->inOut->progressAdvance();

            // Header
            if (0 === $key) {
                continue;
            }
            // Ignore end of file
            if ($file->eof()) {
                break;
            }
            ++$numberLine;

            // Récupération de ligne au format Array
            $item = $this->getArrayItem((array) $row, $file->key());
            // Recherche la transaction associé
            $result = $this->findTransaction($item);

            // PAs de transaction trouvé :(
            if (0 === count($result)) {
                $outRows[] = $this->getOutRow($item, 'Aucune opération trouvé');
                continue;
            }

            // On se trouve dans la cas d'un virement tagué par HomeBank
            if ('0' === $item['payment']) {
                $error = false;
                /** @var Transaction $trt */
                foreach ($result as $trt) {
                    // Recherche si les transactions trouvées ne sont pas des virements
                    if (Payment::INTERNAL !== $trt->getPayment()->getValue()) {
                        $outRows[] = $this->getOutRow($item, sprintf('Opération trouvé mais paiement trouvé virement <> %s', $trt->getPayment()->getLabel()));
                        $error = true;
                        break;
                    }
                }
                if ($error) {
                    continue;
                }
            } else {
                if (count($result) > 1) {
                    $outRows[] = $this->getOutRow($item, '2 opérations ont été trouvées');
                    continue;
                }
            }

            $this->updateTransaction($result[0], $item);
            ++$numberFound;
        }
        $this->entityManager->flush();
        $this->inOut->progressFinish();

        $this->inOut->table(['Date', 'Compte', 'Tiers', 'Catégorie', 'Montant', 'Virement / Error'], $outRows);

        $this->inOut->writeln(sprintf('Nombre total dans le fichier      : <info>%s</info>', $numberLine));
        $this->inOut->writeln(sprintf('Nombre de transaction traités     : <info>%s</info>', $numberFound));
        $this->inOut->writeln(sprintf('Nombre de transaction avec alerte : <error>%s</error>', $numberLine - $numberFound));

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
    public function getArrayItem(?array $line, int $numLine): array
    {
        if (10 !== count($line)) {
            $this->inOut->error(sprintf('La ligne %s ne contient pas les 10 colonnes', $numLine));
        }

        [$account, $date, $payment, $info, $recipient, $memo, $amount, $state, $category, $tags] = $line;

        return [
            'account' => $account,
            'date' => $date,
            'payment' => $payment,
            'info' => $info,
            'recipient' => $recipient,
            'memo' => $memo,
            'amount' => $amount,
            'state' => $state,
            'category' => $category,
            'tags' => $tags,
        ];
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
            ->innerJoin('trt.recipient', 'rec')
            ->andWhere('trt.amount = :amount')
            ->andWhere('trt.date = :date')
            ->andWhere('trt.recipient = :recipient')
            ->andWhere('trt.account = :account')
            ->setParameter('amount', $this->getAmount($item['amount']))
            ->setParameter('date', $this->getDate($item['date']))
            ->setParameter('recipient', $this->getRecipient($item['recipient']))
            ->setParameter('account', $this->getAccount($item['account']))
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Met à jour la transaction avec le mode de paiement trouvé.
     *
     * @param Transaction   $transaction
     * @param array<string> $item
     */
    private function updateTransaction(Transaction $transaction, array $item): void
    {
        $transaction->setPayment($this->helper->getPayment($item['payment']));
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
            $item['recipient'],
            $item['category'],
            number_format($this->getAmount($item['amount']), 2, '.', ' '),
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
        $objDate = DateTime::createFromFormat('m/d/Y', $date);
        if (false === $objDate) {
            $objDate = new DateTime('1970-01-01');
        }

        return $objDate->format($format);
    }

    /**
     * Retourne le montant en virgule flotante.
     *
     * @param string $amount
     *
     * @return float
     */
    private function getAmount(string $amount): float
    {
        return (float) str_replace(',', '.', $amount);
    }

    /**
     * Retourne le bénéficiaire.
     *
     * @param string $recipient
     *
     * @return Recipient
     */
    public function getRecipient(string $recipient): Recipient
    {
        if ('' === $recipient) {
            $recipient = Recipient::VIRT_NAME;
        }

        return $this->helper->getRecipient($recipient);
    }

    /**
     * Retourne le compte.
     *
     * @param string $account
     *
     * @return Account
     */
    public function getAccount(string $account): Account
    {
        return $this->helper->getAccount($account);
    }
}
