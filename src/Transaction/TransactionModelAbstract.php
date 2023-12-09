<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Transaction;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Entity\TransactionStock;
use App\Entity\TransactionVehicle;
use App\Repository\TransactionRepository;
use App\Values\Payment;
use App\Values\StockPosition;
use App\Values\TransactionType;
use App\WorkFlow\Balance;
use App\WorkFlow\Transfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Abstraction des modèles de transactions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class TransactionModelAbstract implements TransactionModelInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionRepository
     */
    protected $repository;

    /**
     * Transaction en cours et valider par le formulaire.
     *
     * @var Transaction
     */
    protected $transaction;

    /**
     * Transaction avant la validation du formulaire.
     *
     * @var Transaction
     */
    private $before;

    /**
     * @var Balance
     */
    private $balanceHelper;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
        $this->repository = $this->entityManager->getRepository(Transaction::class); /** @phpstan-ignore-line */
        $this->balanceHelper = new Balance($this->entityManager);

        $this->transaction = new Transaction();
        $this->before = clone $this->transaction;
    }

    /**
     * Initialise la transaction lors de sa création.
     *
     * @return TransactionModelInterface
     */
    public function init(): TransactionModelInterface
    {
        $this->transaction->setType($this->getTransactionType());
        $this->transaction->setCategory($this->getCategory());
        $this->transaction->setPayment($this->getPayment());
        $this->transaction->setRecipient($this->getRecipient());

        if (TransactionType::VEHICLE === $this->transaction->getType()->getValue()) {
            $transacVeh = new TransactionVehicle();
            $this->transaction->setTransactionVehicle($transacVeh);
        }

        if (TransactionType::STOCKEXCHANGE === $this->transaction->getType()->getValue()) {
            $transacStock = new TransactionStock();
            $this->transaction->setTransactionStock($transacStock);
            $this->transaction->getTransactionStock()->setPosition($this->getPosition());
        }

        return $this;
    }

    /**
     * Affecte la transaction.
     *
     * @param Transaction $transaction
     *
     * @return TransactionModelInterface
     */
    public function setTransaction(Transaction $transaction): TransactionModelInterface
    {
        $this->transaction = $transaction;

        if ($transaction->getTransfer()) {
            // Dans le cas d'un virement, on prend la transaction de crédit
            if ($transaction->getAmount() < 0) {
                $this->transaction = $transaction->getTransfer();
            }
        }

        $this->before = clone $this->transaction;

        return $this;
    }

    /**
     * Affecte un compte à la transaction.
     *
     * @param Account $account
     *
     * @return TransactionModelInterface
     */
    public function setAccount(Account $account): TransactionModelInterface
    {
        if (TransactionType::STOCKEXCHANGE === $this->transaction->getType()->getValue()) {
            $this->transaction->setAccount($account->getAccAssoc());
            $this->transaction->getTransactionStock()->setAccount($account);
        } else {
            $this->transaction->setAccount($account);
        }

        return $this;
    }

    /**
     * Affecte des données contenu dans un tableau.
     *
     * @param array<mixed> $datas
     *
     * @return TransactionModelInterface
     */
    public function setDatas(array $datas): TransactionModelInterface
    {
        $accessor = new PropertyAccessor();
        foreach ($datas as $key => $value) {
            switch ($key) {
                case 'transactionStock':
                    $transactionStock = $this->transaction->getTransactionStock();
                    foreach ($value as $key2 => $value2) {
                        $accessor->setValue($transactionStock, $key2, $value2);
                    }
                    break;

                case 'transactionVehicle':
                    $transactionVehicle = $this->transaction->getTransactionVehicle();
                    foreach ($value as $key2 => $value2) {
                        $accessor->setValue($transactionVehicle, $key2, $value2);
                    }
                    break;

                default:
                    $accessor->setValue($this->transaction, $key, $value);
                    break;
            }
        }

        return $this;
    }

    /**
     * Ajoute la transaction en base.
     *
     * @param FormInterface|null $form
     */
    public function insert(?FormInterface $form = null): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            if ($form->has('invest')) {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData(), $form->get('invest')->getData());
            } else {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            }
            $transfer->persist();
            $this->entityManager->flush();
        } else {
            $this->correctAmount();
            $this->entityManager->persist($this->transaction);
            $this->entityManager->flush();
        }
        $this->calculateBalance();
    }

    /**
     * Ajoute la transacion en base lors d'un import.
     *
     * @param array<mixed>|null $datas
     */
    public function insertModeImport(?array $datas = null): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->makeTransfer($datas['source'], $datas['target'], $datas['invest']);
            $transfer->persist();
        } else {
            $this->correctAmount();
            $this->entityManager->persist($this->transaction);
        }
    }

    /**
     * Mets à jour une transaction.
     *
     * @param FormInterface|null $form
     */
    public function update(?FormInterface $form = null): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            if ($form->has('invest')) {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData(), $form->get('invest')->getData());
            } else {
                $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            }
            $this->entityManager->flush();
        } else {
            $this->correctAmount();
            $this->entityManager->flush();
        }
        $this->calculateBalance();
    }

    /**
     * Supprime une transaction.
     */
    public function delete(): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->remove();
        } else {
            $this->transaction->setAmount(0.0);
            $this->entityManager->remove($this->transaction);
            $this->entityManager->flush();
            $transfer = null;
        }
        $this->calculateBalance();
    }

    /**
     * Retourne la transaction.
     *
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Vérifie le formulaire.
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    public function checkForm(FormInterface $form): bool
    {
        return true;
    }

    /**
     * Si c'est un virement.
     *
     * @return bool
     */
    public function isTransfer(): bool
    {
        return TransactionType::TRANSFER === $this->transaction->getType()->getValue();
    }

    /**
     * Retourne le type de transaction par défaut.
     *
     * @return TransactionType
     */
    protected function getTransactionType(): TransactionType
    {
        return new TransactionType(TransactionType::STANDARD);
    }

    /**
     * Retourne la catégorie par défaut.
     *
     * @return Category|null
     */
    protected function getCategory(): ?Category
    {
        return null;
    }

    /**
     * Retourne le type de paiement par défaut.
     *
     * @return Payment|null
     */
    protected function getPayment(): ?Payment
    {
        return null;
    }

    /**
     * Retourne le bénéficiare par défaut.
     *
     * @return Recipient|null
     */
    protected function getRecipient(): ?Recipient
    {
        return null;
    }

    /**
     * Retourne la position de l'opération boursière.
     *
     * @return StockPosition|null
     */
    protected function getPosition(): ?StockPosition
    {
        return null;
    }

    /**
     * Corrige le montant en fonction si c'est une recette ou une dépense.
     */
    private function correctAmount(): void
    {
        $amount = $this->transaction->getAmount();
        if (Category::RECETTES === $this->transaction->getCategory()->getType()) {
            $this->transaction->setAmount(abs($amount));
        } else {
            $this->transaction->setAmount(abs($amount) * -1);
        }

        if (TransactionType::STOCKEXCHANGE === $this->transaction->getType()->getValue() && $this->transaction->getTransactionStock()->getPrice()) {
            $fee = abs($amount) - ($this->transaction->getTransactionStock()->getVolume() * $this->transaction->getTransactionStock()->getPrice());
            $this->transaction->getTransactionStock()->setFee(abs(round($fee, 2)));
        }
    }

    /**
     * Recalcule les soldes.
     */
    private function calculateBalance(): void
    {
        if ($this->isTransfer()) {
            $this->balanceHelper->updateBalanceAfter($this->transaction->getTransfer(), $this->before);
        }
        $this->balanceHelper->updateBalanceAfter($this->transaction, $this->before);
    }

    /**
     * Retourne la catégorie à utiliser.
     *
     * @param bool   $type
     * @param string $code
     *
     * @return Category
     */
    protected function getCategoryByCode(bool $type, string $code): Category
    {
        /** @phpstan-ignore-next-line */
        return $this->entityManager->getRepository(Category::class)->findOneByCode($type, $code);
    }

    /**
     * Retourne le bénéficiare interne (Moi-même).
     *
     * @return Recipient
     */
    protected function findRecipientInternal(): Recipient
    {
        /** @phpstan-ignore-next-line */
        return $this->entityManager->getRepository(Recipient::class)->findInternal();
    }
}
