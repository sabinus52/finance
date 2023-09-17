<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230410133636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transaction_vehicle (id INT AUTO_INCREMENT NOT NULL, transaction_id INT NOT NULL, vehicle_id INT NOT NULL, distance INT DEFAULT NULL, volume DOUBLE PRECISION DEFAULT NULL, UNIQUE INDEX UNIQ_6D2AD8642FC0CB0F (transaction_id), INDEX IDX_6D2AD864545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transaction_vehicle ADD CONSTRAINT FK_6D2AD8642FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('ALTER TABLE transaction_vehicle ADD CONSTRAINT FK_6D2AD864545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE vehicle CHANGE registered_at registered_at DATE DEFAULT NULL, CHANGE bought_at bought_at DATE NOT NULL, CHANGE sold_at sold_at DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction_vehicle DROP FOREIGN KEY FK_6D2AD8642FC0CB0F');
        $this->addSql('ALTER TABLE transaction_vehicle DROP FOREIGN KEY FK_6D2AD864545317D1');
        $this->addSql('DROP TABLE transaction_vehicle');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE vehicle CHANGE registered_at registered_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE bought_at bought_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE sold_at sold_at DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
