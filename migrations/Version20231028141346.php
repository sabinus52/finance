<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231028141346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_wallet (id INT AUTO_INCREMENT NOT NULL, stock_id INT NOT NULL, account_id INT NOT NULL, volume DOUBLE PRECISION NOT NULL, INDEX IDX_B64E4089DCD6110 (stock_id), INDEX IDX_B64E40899B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction_stock (id INT AUTO_INCREMENT NOT NULL, stock_id INT NOT NULL, account_id INT NOT NULL, position SMALLINT NOT NULL, volume DOUBLE PRECISION DEFAULT NULL, price DOUBLE PRECISION DEFAULT NULL, fee DOUBLE PRECISION NOT NULL, INDEX IDX_EA058620DCD6110 (stock_id), INDEX IDX_EA0586209B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock_wallet ADD CONSTRAINT FK_B64E4089DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE stock_wallet ADD CONSTRAINT FK_B64E40899B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE transaction_stock ADD CONSTRAINT FK_EA058620DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE transaction_stock ADD CONSTRAINT FK_EA0586209B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD transaction_stock_id INT DEFAULT NULL, CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D143BF4878 FOREIGN KEY (transaction_stock_id) REFERENCES transaction_stock (id)');
        $this->addSql('CREATE INDEX IDX_723705D143BF4878 ON transaction (transaction_stock_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D143BF4878');
        $this->addSql('ALTER TABLE stock_wallet DROP FOREIGN KEY FK_B64E4089DCD6110');
        $this->addSql('ALTER TABLE stock_wallet DROP FOREIGN KEY FK_B64E40899B6B5FBA');
        $this->addSql('ALTER TABLE transaction_stock DROP FOREIGN KEY FK_EA058620DCD6110');
        $this->addSql('ALTER TABLE transaction_stock DROP FOREIGN KEY FK_EA0586209B6B5FBA');
        $this->addSql('DROP TABLE stock_wallet');
        $this->addSql('DROP TABLE transaction_stock');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX IDX_723705D143BF4878 ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_stock_id, CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
    }
}
