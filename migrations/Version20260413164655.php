<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413164655 extends AbstractMigration
{public function getDescription(): string
    {
        return 'Add placesReserves column to activite table and create user_activite_booking junction table';
    }
 
    public function up(Schema $schema): void
    {
        // Add placesReserves column to activite table
        $this->addSql('ALTER TABLE activite ADD COLUMN places_reserves INT NOT NULL DEFAULT 0');
 
        // Create junction table for user-activite booking relationship
        $this->addSql('CREATE TABLE user_activite_booking (
            id_user INT NOT NULL,
            id_activite INT NOT NULL,
            PRIMARY KEY(id_user, id_activite),
            FOREIGN KEY(id_user) REFERENCES user(id_user) ON DELETE CASCADE,
            FOREIGN KEY(id_activite) REFERENCES activite(idActivite) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
 
    public function down(Schema $schema): void
    {
        // Drop the junction table
        $this->addSql('DROP TABLE IF EXISTS user_activite_booking');
 
        // Remove placesReserves column
        $this->addSql('ALTER TABLE activite DROP COLUMN places_reserves');
    }
}
