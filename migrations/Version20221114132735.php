<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221114132735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account ADD short_name VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE institution ADD short_name VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE institution ADD code_swift VARCHAR(12) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP short_name');
        $this->addSql('ALTER TABLE institution DROP short_name');
        $this->addSql('ALTER TABLE institution DROP code_swift');
    }
}
