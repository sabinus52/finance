<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221111133417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B365660ECC836F9 ON stock (symbol)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B365660190347EC ON stock (code_isin)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B3656605E237E06 ON stock (name)');
        $this->addSql('ALTER TABLE stock_portfolio ADD transaction_id INT NOT NULL');
        $this->addSql('ALTER TABLE stock_portfolio ADD CONSTRAINT FK_3170F68A2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3170F68A2FC0CB0F ON stock_portfolio (transaction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_4B365660ECC836F9 ON stock');
        $this->addSql('DROP INDEX UNIQ_4B365660190347EC ON stock');
        $this->addSql('DROP INDEX UNIQ_4B3656605E237E06 ON stock');
        $this->addSql('ALTER TABLE stock_portfolio DROP FOREIGN KEY FK_3170F68A2FC0CB0F');
        $this->addSql('DROP INDEX UNIQ_3170F68A2FC0CB0F ON stock_portfolio');
        $this->addSql('ALTER TABLE stock_portfolio DROP transaction_id');
    }
}
