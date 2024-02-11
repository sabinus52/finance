<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Import;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Transaction\TransactionModelRouter;
use App\Values\StockPosition;
use App\WorkFlow\Balance;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Classe d'aide pour l'omport des transactions venant d'un programme extérieur.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class Helper
{
    private readonly TransactionModelRouter $router;

    /**
     * Statistiques de l'import.
     *
     * @var Statistic
     */
    public $statistic;

    /**
     * Liste des données associées (Account, Recepient, ...).
     *
     * @var AssocDatas
     */
    public $assocDatas;

    /**
     * Constructeur.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->router = new TransactionModelRouter($this->entityManager);
        $this->statistic = new Statistic();
        $this->assocDatas = new AssocDatas($this->entityManager);
    }

    /**
     * Création et insère dans la base une transaction standard.
     *
     * @param string|null $project
     */
    public function createTransationStandard(QifItem $item, string $project = null): void
    {
        $modelTransac = $this->router->createStandardByType($item->getAmount() > 0);
        $modelTransac->setAccount($item->getAccount())
            ->setDatas([
                'date' => $item->getDate(),
                'amount' => $item->getAmount(),
                'recipient' => $item->getRecipient(),
                'category' => $item->getCategory(),
                'payment' => $item->getPayment(),
                'state' => $item->getState(),
                'memo' => $item->getMemo(),
            ])
        ;
        if (null !== $project) {
            $modelTransac->setDatas(['project' => $this->assocDatas->getProject($project)]);
        }
        $modelTransac->insertModeImport();
        $this->statistic->incTransaction($modelTransac->getTransaction());
    }

    /**
     * Création et insère dans la base un transfert (virement ou investissement).
     *
     * @param string     $type         Virement ou investissement
     * @param string     $target       Compte cible
     * @param float|null $amountInvest Montant invsti sur le compte de placement
     */
    public function createTransactionTransfer(QifItem $item, string $type, string $target, float $amountInvest = null): void
    {
        if (null === $amountInvest) {
            $amountInvest = abs($item->getAmount());
        }
        $modelTransac = $this->router->createTransferByCategory($type);
        $modelTransac->setDatas([
            'date' => $item->getDate(),
            'amount' => abs($item->getAmount()),
            'state' => $item->getState(),
            'memo' => $item->getMemo(),
        ])->insertModeImport([
            'source' => $item->getAccount(),
            'target' => $this->assocDatas->getAccount($target),
            'invest' => $amountInvest,
        ]);
        $this->statistic->incTransaction($modelTransac->getTransaction());
    }

    /**
     * Création et insère dans la base une transaction boursière.
     *
     * @param float|null $volume
     * @param float|null $price
     */
    public function createTransationStockPosition(QifItem $item, Account $wallet, int $position, string $stock, ?float $volume, ?float $price): void
    {
        $position = new StockPosition($position);

        $modelTransac = $this->router->createStock($position);
        $modelTransac->setDatas([
            'account' => $item->getAccount(),
            'date' => $item->getDate(),
            'amount' => $item->getAmount(),
            'state' => $item->getState(),
            'memo' => $item->getMemo(),
            'transactionStock' => [
                'account' => $wallet,
                'stock' => $this->assocDatas->getStock($stock),
                'volume' => $volume,
                'price' => $price,
            ],
        ]);

        // Si dividendes
        if (null !== $volume) {
            $modelTransac->setDatas([
                'transactionStock' => [
                    'volume' => $volume,
                    'price' => $price,
                ],
            ]);
        }

        $modelTransac->insertModeImport();
    }

    /**
     * Création et insère dans la base une réévaluation.
     */
    public function createRevaluation(Account $account, \DateTime $date, float $amount): void
    {
        $modelTransac = $this->router->createRevaluation($date);
        $modelTransac->setDatas([
            'account' => $account,
            'balance' => $amount,
        ])
            ->insertModeImport()
        ;
    }

    /**
     * Calcule le solde de tous les comptes.
     */
    public function calulateBalance(): void
    {
        $balance = new Balance($this->entityManager);

        /** @var AccountRepository $repository */
        $repository = $this->entityManager->getRepository(Account::class);
        $accounts = $repository->findAll();

        /** @var Account $account */
        foreach ($accounts as $account) {
            $balance->updateBalanceFromScratch($account);
            $this->statistic->setAccountData($account);
        }
    }
}
