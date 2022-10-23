<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Recipient;
use App\Entity\Transaction;
use App\Values\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Ajout d'une nouvelle transaction en base.
     *
     * @param Transaction $entity
     * @param bool        $flush
     */
    public function add(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Ajoute d'un nouveau virement en base.
     *
     * @param Transaction $entity Transaction CIBLE obligatoirement (= compte créditeur)
     * @param Account     $source Compte débiteur
     * @param Account     $target Compte créditeur
     * @param bool        $flush
     */
    public function addTransfer(Transaction $entity, Account $source, Account $target, bool $flush = false): void
    {
        $transfer = new Transaction();
        $entity->setTransfer($transfer);
        $transfer->setTransfer($entity);
        $this->setDataTransfer($entity, $source, $target);

        $this->add($entity, $flush);
    }

    /**
     * Met à jour une transaction en base.
     *
     * @param bool $flush
     */
    public function update(bool $flush = false): void
    {
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Met à jour un virement en base.
     *
     * @param Transaction $entity Transaction CIBLE obligatoirement (= compte créditeur)
     * @param Account     $source Compte débiteur
     * @param Account     $target Compte créditeur
     * @param bool        $flush
     */
    public function updateTransfer(Transaction $entity, Account $source, Account $target, bool $flush = false): void
    {
        $this->setDataTransfer($entity, $source, $target);

        $this->update($flush);
    }

    /**
     * Affecte les données similaires dans les 2 transactions d'un virement.
     *
     * @param Transaction $entity
     * @param Account     $source
     * @param Account     $target
     */
    private function setDataTransfer(Transaction $entity, Account $source, Account $target): void
    {
        /** @var CategoryRepository $repoCat */
        $repoCat = $this->getEntityManager()->getRepository(Category::class);

        // Transaction créditeur
        $entity->setAmount(abs($entity->getAmount()));
        $entity->setAccount($target);
        $entity->setPayment(new Payment(Payment::INTERNAL));
        $entity->setRecipient($this->getEntityManager()->getRepository(Recipient::class)->find(1));
        $entity->setCategory($repoCat->findTransfer(Category::RECETTES));

        // Transaction débiteur
        $entity->getTransfer()->setAmount($entity->getAmount() * -1);
        $entity->getTransfer()->setAccount($source);
        $entity->getTransfer()->setRecipient($entity->getRecipient());
        $entity->getTransfer()->setCategory($repoCat->findTransfer(Category::DEPENSES));
        $entity->getTransfer()->setPayment($entity->getPayment());
        $entity->getTransfer()->setDate($entity->getDate());
    }

    /**
     * Suppirme une transation en base.
     *
     * @param Transaction $entity
     */
    public function remove(Transaction $entity): void
    {
        // Si c'est un virement
        if ($entity->getTransfer()) {
            $transfer = $entity->getTransfer();
            $transfer->setTransfer(null);
            $entity->setTransfer(null);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->remove($transfer);
        }

        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Toutes les transactions pour un compte donné.
     *
     * @param Account      $account
     * @param array<mixed> $filter
     *
     * @return Transaction[]
     */
    public function findByAccount(Account $account, array $filter = []): array
    {
        $query = $this->createQueryBuilder('trt')
            ->addSelect('rcp')
            ->addSelect('cat')
            ->addSelect('prt')
            ->addSelect('tsf')
            ->innerJoin('trt.recipient', 'rcp')
            ->innerJoin('trt.category', 'cat')
            ->innerJoin('cat.parent', 'prt')
            ->leftJoin('trt.transfer', 'tsf')
            ->leftJoin('tsf.account', 'tac')
            ->andWhere('trt.account = :account')
            ->setParameter('account', $account)
            ->orderBy('trt.date', 'ASC')
            ->addOrderBy('trt.id', 'ASC')
        ;

        foreach ($filter as $key => $value) {
            // Si null ou vide, pas de filtre
            if ('' === $value || null === $value) {
                continue;
            }
            if ('range' === $key) {
                $query->andWhere('trt.date BETWEEN :start AND :end')
                    ->setParameter('start', $value[0])
                    ->setParameter('end', $value[1])
                ;
            } else {
                $query->andWhere(sprintf('trt.%s = :%s', $key, $key))
                    ->setParameter($key, $value)
                ;
            }
        }

        return $query->getQuery()->getResult();
    }
}
