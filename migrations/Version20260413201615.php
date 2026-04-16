<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413201615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE code_promo (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(32) NOT NULL, discount INT NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, is_used TINYINT NOT NULL, is_universal TINYINT DEFAULT 0 NOT NULL, max_uses INT DEFAULT 1 NOT NULL, current_uses INT DEFAULT 0 NOT NULL, client_id INT NOT NULL, reservation_id INT NOT NULL, UNIQUE INDEX UNIQ_5C4683B777153098 (code), INDEX IDX_5C4683B719EB6921 (client_id), INDEX IDX_5C4683B7B83297E7 (reservation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE code_promo ADD CONSTRAINT FK_5C4683B719EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE code_promo ADD CONSTRAINT FK_5C4683B7B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE code_promo DROP FOREIGN KEY FK_5C4683B719EB6921');
        $this->addSql('ALTER TABLE code_promo DROP FOREIGN KEY FK_5C4683B7B83297E7');
        $this->addSql('DROP TABLE code_promo');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
