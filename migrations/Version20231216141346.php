<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231216141346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE schedule (id INT AUTO_INCREMENT NOT NULL, state TINYINT(1) NOT NULL, do_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', frequency SMALLINT NOT NULL, period VARCHAR(1) NOT NULL, number SMALLINT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE model ADD schedule_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE model ADD CONSTRAINT FK_D79572D9A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES schedule (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D79572D9A40BC2D5 ON model (schedule_id)');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE model DROP FOREIGN KEY FK_D79572D9A40BC2D5');
        $this->addSql('DROP TABLE schedule');
        $this->addSql('ALTER TABLE project CHANGE category category SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX UNIQ_D79572D9A40BC2D5 ON model');
        $this->addSql('ALTER TABLE model DROP schedule_id');
        $this->addSql('ALTER TABLE transaction CHANGE type type SMALLINT DEFAULT 0 NOT NULL');
    }
}
