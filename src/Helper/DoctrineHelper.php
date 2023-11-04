<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Aide sur le fonctions de la base de données.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
final class DoctrineHelper
{
    /**
     * @var EntityManagerInterface
     */
    public $entityManager;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->entityManager = $manager;
    }

    /**
     * Purge la table.
     *
     * @param string $entityClass
     *
     * @return string|null
     */
    public function truncate(string $entityClass): ?string
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $table = $this->getTableNameForEntity($entityClass);

        try {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
            $connection->executeStatement($platform->getTruncateTableSQL($table, false /* whether to cascade */));
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return null;
    }

    public function getTableNameForEntity(string $entityClass): string
    {
        $metadata = $this->entityManager->getClassMetadata($entityClass);

        return $metadata->getTableName();
    }
}
