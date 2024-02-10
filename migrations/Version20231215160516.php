<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231215160516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE model (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, recipient_id INT NOT NULL, category_id INT NOT NULL, vehicle_id INT DEFAULT NULL, transfer_id INT DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, type SMALLINT NOT NULL, payment SMALLINT NOT NULL, memo VARCHAR(255) DEFAULT NULL, INDEX IDX_D79572D99B6B5FBA (account_id), INDEX IDX_D79572D9E92F8F78 (recipient_id), INDEX IDX_D79572D912469DE2 (category_id), INDEX IDX_D79572D9545317D1 (vehicle_id), INDEX IDX_D79572D9537048AF (transfer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D99B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D9E92F8F78 FOREIGN KEY (recipient_id) REFERENCES recipient (id)');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D9545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D9537048AF FOREIGN KEY (transfer_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE model DROP FOREIGN KEY FK_D79572D99B6B5FBA');
        $this->addSql('ALTER TABLE model DROP FOREIGN KEY FK_D79572D9E92F8F78');
        $this->addSql('ALTER TABLE model DROP FOREIGN KEY FK_D79572D912469DE2');
        $this->addSql('ALTER TABLE model DROP FOREIGN KEY FK_D79572D9545317D1');
        $this->addSql('ALTER TABLE model DROP FOREIGN KEY FK_D79572D9537048AF');
        $this->addSql('DROP TABLE model');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
    }
}
