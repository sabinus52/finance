<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;
use App\Values\Payment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * Classe d'aide de manipulation des transactions.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class TransactionHelper
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TransactionRepository
     */
    private $repository;

    /**
     * Transaction en cours et valider par le formulaire.
     *
     * @var Transaction
     */
    private $transaction;

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
     * @param EntityManagerInterface $manager
     * @param Transaction            $transaction
     */
    public function __construct(EntityManagerInterface $manager, Transaction $transaction)
    {
        $this->entityManager = $manager;
        $this->repository = $this->entityManager->getRepository(Transaction::class); /** @phpstan-ignore-line */
        $this->transaction = $transaction;
        $this->before = clone $transaction;
        $this->balanceHelper = new Balance($this->entityManager);
    }

    /**
     * Ajoute la transaction en base.
     */
    private function persist(): void
    {
        $this->entityManager->persist($this->transaction);
        $this->flush();
    }

    /**
     * Mets à jour la transaction en base.
     */
    private function flush(): void
    {
        $this->entityManager->flush();
        $this->balanceHelper->updateBalanceAfter($this->transaction, $this->before->getDate());
    }

    /**
     * Supprime une transaction.
     */
    public function remove(): void
    {
        $this->entityManager->remove($this->transaction);
        $this->flush();
    }

    // === TRANSACTION STANDARD ===========================================================

    /**
     * Ajoute en base une transaction standard.
     */
    public function persistStandard(): void
    {
        $this->persist();
    }

    /**
     * Modifie en base une transaction standard.
     */
    public function updateStandard(): void
    {
        $this->flush();
    }

    /**
     * Vérifie la validation du formulaire d'une transaction standard.
     *
     * @return bool
     */
    public function checkStandard(FormInterface $form): bool
    {
        if ($this->transaction->getAmount() > 0 && Category::DEPENSES === $this->transaction->getCategory()->getType()) {
            $form->get('category')->addError(new FormError(''));
            $form->get('amount')->addError(new FormError(''));

            return false;
        }
        if ($this->transaction->getAmount() < 0 && Category::RECETTES === $this->transaction->getCategory()->getType()) {
            $form->get('category')->addError(new FormError(''));
            $form->get('amount')->addError(new FormError(''));

            return false;
        }

        return true;
    }

    // === TRANSACTION VALORISATION =======================================================

    /**
     * Ajoute en base une transaction de valorisation de placement.
     */
    public function persistValorisation(): void
    {
        $last = $this->initValorisation();
        if (null !== $last) {
            $variation = $this->transaction->getBalance() - $last->getBalance();
            $this->transaction->setAmount($variation);
            $this->transaction->setCategory(self::getCategoryByCode($this->entityManager, ($variation > 0), Category::REVALUATION));
        }

        $this->persist();
    }

    /**
     * Modifie en base une transaction de valorisation de placement.
     */
    public function updateValorisation(): void
    {
        $variation = $this->transaction->getBalance() - $this->before->getBalance() + $this->before->getAmount();
        $this->transaction->setAmount($variation);
        $this->transaction->setCategory(self::getCategoryByCode($this->entityManager, ($variation > 0), Category::REVALUATION));

        $this->flush();
    }

    /**
     * Vérifie la validation du formulaire d'une transaction de valorisation de placement.
     *
     * @return bool
     */
    public function checkValorisation(FormInterface $form): bool
    {
        // Recherche si une transaction existe déjà
        $trt = $this->repository->findOneValorisation($this->transaction);

        if (null !== $trt) {
            $form->get('date')->addError(new FormError('Déjà existant'));

            return false;
        }

        return true;
    }

    /**
     * Initialisation la transaction de valorisation avec la précédente.
     *
     * @return Transaction|null
     */
    public function initValorisation(): ?Transaction
    {
        $last = $this->repository->findOneLastValorisation($this->transaction->getAccount());
        $date = new DateTime();
        if (null !== $last) {
            $date = clone $last->getDate()->modify('+ 15 days');
        }

        self::setTransactionInternal($this->entityManager, $this->transaction);
        $this->transaction->setDate($date->modify('last day of this month'));
        $this->transaction->setAmount(0);
        $this->transaction->setCategory(new Category());

        return $last;
    }

    // === FUNCTIONS STATIQUES ====================================================================

    /**
     * Initialise une transaction interne.
     */
    public static function setTransactionInternal(EntityManagerInterface $manager, Transaction $transaction): void
    {
        $transaction->setPayment(new Payment(Payment::INTERNAL));
        $transaction->setRecipient($manager->getRepository(Recipient::class)->find(1));
    }

    /**
     * Retourne une catégorie en fonction de son code.
     *
     * @param EntityManagerInterface $manager
     * @param bool                   $type
     * @param string                 $code
     *
     * @return Category
     */
    public static function getCategoryByCode(EntityManagerInterface $manager, bool $type, string $code): Category
    {
        /** @var CategoryRepository $repo */
        $repo = $manager->getRepository(Category::class);

        return $repo->findOneByCode($type, $code);
    }
}
