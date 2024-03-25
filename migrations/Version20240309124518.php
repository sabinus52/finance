<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240309124518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD theme SMALLINT DEFAULT 0 NOT NULL, DROP expiresat, CHANGE enabled enabled TINYINT(1) NOT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_activity last_activity DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD expiresat DATE DEFAULT NULL, DROP expires_at, DROP theme, CHANGE enabled enabled SMALLINT DEFAULT 1 NOT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL, CHANGE last_activity last_activity DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
    }
}
