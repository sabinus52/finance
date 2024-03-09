<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Values\AccountBalance;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240309124829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate field account.balance in OBJECT to JSON';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->connection->fetchAllAssociative('SELECT id, balance FROM account') as $account) {
            if (str_starts_with($account['balance'], 'O')) {
                $this->addSql(sprintf(
                    "UPDATE account SET balance = '%s' WHERE id = %s",
                    $this->convertObject2Json($account['balance']),
                    $account['id'])
                );
            }
        }
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account CHANGE balance balance LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account CHANGE balance balance LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\'');
    }

    private function convertObject2Json(?string $object): string
    {
        /** @var AccountBalance $balance */
        $balance = unserialize($object);

        $result = [
            'balance' => $balance->getBalance(),
            'reconbalance' => $balance->getReconBalance(),
            'reconcurrent' => $balance->getReconCurrent(),
            'investment' => $balance->getInvestment(),
            'repurchase' => $balance->getRepurchase(),
        ];

        return (string) json_encode($result);
    }
}
