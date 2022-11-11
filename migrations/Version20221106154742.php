<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221106154742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE stock_portfolio (id INT AUTO_INCREMENT NOT NULL, stock_id INT NOT NULL, account_id INT NOT NULL, date DATE NOT NULL, position SMALLINT NOT NULL, volume DOUBLE PRECISION DEFAULT DEFAULT NULL, price DOUBLE PRECISION DEFAULT NULL, fee DOUBLE PRECISION DEFAULT NULL, total DOUBLE PRECISION NOT NULL, INDEX IDX_A9ED1062DCD6110 (stock_id), INDEX IDX_A9ED10629B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, symbol VARCHAR(10) NOT NULL, code_isin VARCHAR(12) NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock_price (id INT AUTO_INCREMENT NOT NULL, stock_id INT NOT NULL, date DATE NOT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_56D83E28DCD6110 (stock_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock_portfolio ADD CONSTRAINT FK_A9ED1062DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE stock_portfolio ADD CONSTRAINT FK_A9ED10629B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE stock_price ADD CONSTRAINT FK_56D83E28DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE account ADD acc_assoc_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A471A9964A FOREIGN KEY (acc_assoc_id) REFERENCES account (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7D3656A471A9964A ON account (acc_assoc_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stock_portfolio DROP FOREIGN KEY FK_A9ED1062DCD6110');
        $this->addSql('ALTER TABLE stock_portfolio DROP FOREIGN KEY FK_A9ED10629B6B5FBA');
        $this->addSql('ALTER TABLE stock_price DROP FOREIGN KEY FK_56D83E28DCD6110');
        $this->addSql('DROP TABLE stock_portfolio');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE stock_price');
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A471A9964A');
        $this->addSql('DROP INDEX UNIQ_7D3656A471A9964A ON account');
        $this->addSql('ALTER TABLE account DROP acc_assoc_id');
    }
}
