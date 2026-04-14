<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414123456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reservation_activite table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reservation_activite (
            id INT AUTO_INCREMENT NOT NULL,
            id_user INT NOT NULL,
            id_activite INT NOT NULL,
            date_activite DATETIME NOT NULL,
            nombre_participants INT NOT NULL,
            statut VARCHAR(20) NOT NULL,
            date_reservation DATETIME NOT NULL,
            INDEX IDX_25C0B7016B3CA4B (id_user),
            INDEX IDX_25C0B701E8AEB980 (id_activite),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        $this->addSql('ALTER TABLE reservation_activite ADD CONSTRAINT FK_25C0B7016B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE reservation_activite ADD CONSTRAINT FK_25C0B701E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite (idActivite)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reservation_activite');
    }
}