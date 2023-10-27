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
use App\Entity\TransactionVehicle;
use App\Entity\Vehicle;
use App\Repository\TransactionRepository;
use App\Values\Payment;
use App\Values\TransactionType;
use App\WorkFlow\Balance;
use App\WorkFlow\Transfer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Abstraction des modèles de transactions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @param Transaction            $transaction
     */
    public function __construct(EntityManagerInterface $manager, Transaction $transaction)
    {
        $this->entityManager = $manager;
        $this->repository = $this->entityManager->getRepository(Transaction::class); /** @phpstan-ignore-line */
        $this->balanceHelper = new Balance($this->entityManager);

        $this->transaction = $transaction;
        if ($transaction->getTransfer()) {
            // Dans le cas d'un virement, on prend la transaction de crédit
            if ($transaction->getAmount() < 0) {
                $this->transaction = $transaction->getTransfer();
            }
        }

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
        $this->transaction->setAccount($account);

        return $this;
    }

    /**
     * Affecte un véhicule à la transaction.
     *
     * @param Vehicle $vehicle
     *
     * @return TransactionModelInterface
     */
    public function setVehicle(Vehicle $vehicle): TransactionModelInterface
    {
        $transacVeh = new TransactionVehicle();
        $transacVeh->setVehicle($vehicle);
        $this->transaction->setTransactionVehicle($transacVeh);

        return $this;
    }

    /**
     * Ajoute la transaction en base.
     *
     * @param FormInterface|null $form
     */
    public function add(?FormInterface $form = null): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            $transfer->persist();
        } else {
            $this->correctAmount();
            $this->entityManager->persist($this->transaction);
            $this->entityManager->flush();
        }
        $this->calculateBalance();
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
            $transfer->makeTransfer($form->get('source')->getData(), $form->get('target')->getData());
            $transfer->update();
        } else {
            $this->correctAmount();
            $this->entityManager->flush();
        }
        $this->calculateBalance();
    }

    /**
     * Supprime une transaction.
     */
    public function remove(): void
    {
        if ($this->isTransfer()) {
            $transfer = new Transfer($this->entityManager, $this->transaction);
            $transfer->remove();
        } else {
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
    }

    /**
     * Recalcule les soldes.
     */
    private function calculateBalance(): void
    {
        if ($this->isTransfer()) {
            $this->balanceHelper->updateBalanceAfter($this->transaction->getTransfer(), $this->before->getDate());
        }
        $this->balanceHelper->updateBalanceAfter($this->transaction, $this->before->getDate());
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
