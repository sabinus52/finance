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
final class Version20240317165040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account CHANGE opened_at opened_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE closed_at closed_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE balance balance LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL, CHANGE started_at started_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE finish_at finish_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE stock ADD type SMALLINT NOT NULL, CHANGE closed_at closed_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE stock_price CHANGE date date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('UPDATE stock SET type = 1');
        $this->addSql('ALTER TABLE stock_wallet CHANGE price_date price_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE transaction CHANGE date date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE vehicle CHANGE registered_at registered_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE bought_at bought_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE sold_at sold_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account CHANGE opened_at opened_at DATE NOT NULL, CHANGE closed_at closed_at DATE DEFAULT NULL, CHANGE balance balance LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL, CHANGE started_at started_at DATE NOT NULL, CHANGE finish_at finish_at DATE NOT NULL');
        $this->addSql('ALTER TABLE stock DROP type, CHANGE closed_at closed_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE stock_price CHANGE date date DATE NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE date date DATE NOT NULL, CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE vehicle CHANGE registered_at registered_at DATE DEFAULT NULL, CHANGE bought_at bought_at DATE NOT NULL, CHANGE sold_at sold_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE stock_wallet CHANGE price_date price_date DATE DEFAULT NULL');
    }
}
