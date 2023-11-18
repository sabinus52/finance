<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231112131912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B36566086209BDE');
        $this->addSql('DROP INDEX UNIQ_4B36566086209BDE ON stock');
        $this->addSql('ALTER TABLE stock CHANGE fusion_id fusion_from_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660A7DB6BA2 FOREIGN KEY (fusion_from_id) REFERENCES stock (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B365660A7DB6BA2 ON stock (fusion_from_id)');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660A7DB6BA2');
        $this->addSql('DROP INDEX UNIQ_4B365660A7DB6BA2 ON stock');
        $this->addSql('ALTER TABLE stock CHANGE fusion_from_id fusion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B36566086209BDE FOREIGN KEY (fusion_id) REFERENCES stock (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B36566086209BDE ON stock (fusion_id)');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
    }
}
