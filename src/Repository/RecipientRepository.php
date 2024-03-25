<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Recipient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipient>
 *
 * @method Recipient|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recipient|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recipient[]    findAll()
 * @method Recipient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipient::class);
    }

    /**
     * Retourne le bénéficiare interne.
     */
    public function findInternal(): Recipient
    {
        return $this->findOneBy([
            'name' => Recipient::VIRT_NAME,
        ]);
    }

    /**
     * Retoune un tableau associatif des bénéficiares [Carrefour] => Recipient.
     *
     * @return Recipient[]
     */
    public function get4Import(): array
    {
        $result = [];

        /** @var Recipient $recipient */
        foreach ($this->findAll() as $recipient) {
            $result[$recipient->getName()] = $recipient;
        }

        return $result;
    }
}
