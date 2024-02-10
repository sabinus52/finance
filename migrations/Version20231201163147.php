<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231201163147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP recon_balance, DROP recon_current, DROP invested, CHANGE balance balance LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:object)\'');
        $this->addSql('UPDATE account SET balance = NULL');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account ADD recon_balance DOUBLE PRECISION DEFAULT \'0\' NOT NULL, ADD recon_current DOUBLE PRECISION DEFAULT \'0\' NOT NULL, ADD invested DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE balance balance DOUBLE PRECISION DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
    }
}
