<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240610140419 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pv_form (id INT AUTO_INCREMENT NOT NULL, is_public TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, name LONGTEXT DEFAULT NULL, config LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', thank_you VARCHAR(255) DEFAULT NULL, translations LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pv_form_entry (id INT AUTO_INCREMENT NOT NULL, form_id INT DEFAULT NULL, created_at DATETIME NOT NULL, language LONGTEXT NOT NULL, content LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_DE5D66355FF69B7D (form_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE pv_form_entry ADD CONSTRAINT FK_DE5D66355FF69B7D FOREIGN KEY (form_id) REFERENCES pv_form (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pv_form_entry DROP FOREIGN KEY FK_DE5D66355FF69B7D');
        $this->addSql('DROP TABLE pv_form');
        $this->addSql('DROP TABLE pv_form_entry');
    }
}
