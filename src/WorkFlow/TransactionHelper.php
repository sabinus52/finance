<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\WorkFlow;

use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Values\Payment;
use App\Values\TransactionType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Classe de manipulation des transactions.
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
     * @var Transaction
     */
    private $transaction;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     * @param Transaction|null       $transaction
     */
    public function __construct(EntityManagerInterface $manager, ?Transaction $transaction = null)
    {
        $this->entityManager = $manager;
        if (null === $transaction) {
            $transaction = new Transaction();
        }
        $this->transaction = $transaction;
    }

    /**
     * Retourne la transaction.
     *
     * @return Transaction
     */
    public function getTransacion(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Création d'une transaction standard.
     *
     * @return Transaction
     */
    public function createStandard(): Transaction
    {
        $this->transaction->setType(new TransactionType(TransactionType::STANDARD));

        return $this->transaction;
    }

    /**
     * Création d'une transaction de valorisation.
     *
     * @return Transaction
     */
    public function createValorisation(): Transaction
    {
        $this->transaction = $this->createInternal();
        $this->transaction
            ->setType(new TransactionType(TransactionType::REVALUATION))
            ->setDate($this->transaction->getDate()->modify('last day of this month'))
            ->setAmount(0)
            ->setCategory($this->getCategoryByCode(true, Category::REVALUATION))
        ;

        return $this->transaction;
    }

    /**
     * Création d'un virement entre comptes.
     *
     * @return Transaction
     */
    public function createVirement(): Transaction
    {
        $this->transaction = $this->createInternal();
        $this->transaction
            ->setType(new TransactionType(TransactionType::VIREMENT))
            ->setCategory($this->getCategoryByCode(true, Category::VIREMENT))
        ;

        return $this->transaction;
    }

    /**
     * Création d'un virement d'investissement.
     *
     * @return Transaction
     */
    public function createInvestment(): Transaction
    {
        $this->transaction = $this->createInternal();
        $this->transaction
            ->setType(new TransactionType(TransactionType::INVESTMENT))
            ->setCategory($this->getCategoryByCode(true, Category::INVESTMENT))
        ;

        return $this->transaction;
    }

    /**
     * Création d'une transaction de type interne.
     *
     * @return Transaction
     */
    public function createInternal(): Transaction
    {
        $recipient = $this->entityManager->getRepository(Recipient::class)->findInternal(); /** @phpstan-ignore-line */
        $this->transaction->setPayment(new Payment(Payment::INTERNAL));
        $this->transaction->setRecipient($recipient);

        return $this->transaction;
    }

    /**
     * Retourne la catégorie à utiliser.
     *
     * @param bool   $type
     * @param string $code
     *
     * @return Category
     */
    public function getCategoryByCode(bool $type, string $code): Category
    {
        /** @phpstan-ignore-next-line */
        return $this->entityManager->getRepository(Category::class)->findOneByCode($type, $code);
    }
}
