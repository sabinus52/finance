<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231014095840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD transaction_vehicle_id INT DEFAULT NULL, CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1BBC52060 FOREIGN KEY (transaction_vehicle_id) REFERENCES transaction_vehicle (id)');
        $this->addSql('CREATE INDEX IDX_723705D1BBC52060 ON transaction (transaction_vehicle_id)');
        $this->addSql('ALTER TABLE transaction_vehicle DROP FOREIGN KEY FK_6D2AD8642FC0CB0F');
        $this->addSql('DROP INDEX UNIQ_6D2AD8642FC0CB0F ON transaction_vehicle');
        $this->addSql('ALTER TABLE transaction_vehicle DROP transaction_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE transaction_vehicle ADD transaction_id INT NOT NULL');
        $this->addSql('ALTER TABLE transaction_vehicle ADD CONSTRAINT FK_6D2AD8642FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6D2AD8642FC0CB0F ON transaction_vehicle (transaction_id)');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1BBC52060');
        $this->addSql('DROP INDEX IDX_723705D1BBC52060 ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_vehicle_id, CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
    }
}
