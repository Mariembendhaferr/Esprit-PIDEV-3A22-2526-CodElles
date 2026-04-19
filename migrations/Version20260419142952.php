<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419142952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE response');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY `fk_activite_user`');
        $this->addSql('ALTER TABLE activite CHANGE disponibiliteActivite disponibiliteActivite TINYINT NOT NULL');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B87555156B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE activite RENAME INDEX fk_activite_user TO IDX_B87555156B3CA4B');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_activite`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_user`');
        $this->addSql('ALTER TABLE avis CHANGE commentaire commentaire LONGTEXT NOT NULL, CHANGE date_avis date_avis DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF06B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0EBD67F4E FOREIGN KEY (idActivite) REFERENCES activite (idActivite)');
        $this->addSql('ALTER TABLE avis RENAME INDEX fk_avis_user TO IDX_8F91ABF06B3CA4B');
        $this->addSql('ALTER TABLE avis RENAME INDEX fk_avis_activite TO IDX_8F91ABF0EBD67F4E');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY `fk_reclamation_user`');
        $this->addSql('ALTER TABLE reclamation ADD reponse_admin LONGTEXT DEFAULT NULL, ADD date_reponse DATETIME DEFAULT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE priorite priorite VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE6064046B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE reclamation RENAME INDEX fk_reclamation_user TO IDX_CE6064046B3CA4B');
        $this->addSql('ALTER TABLE user CHANGE photo_profil photo_profil VARCHAR(255) DEFAULT NULL, CHANGE role role VARCHAR(20) NOT NULL, CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE date_inscription date_inscription DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX username TO UNIQ_8D93D649F85E0677');
        $this->addSql('ALTER TABLE user RENAME INDEX email TO UNIQ_8D93D649E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE response (id_response INT AUTO_INCREMENT NOT NULL, id_reclamation INT NOT NULL, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_response DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, id_admin INT NOT NULL, INDEX idx_admin (id_admin), INDEX idx_reclamation (id_reclamation), PRIMARY KEY (id_response)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM COMMENT = \'\' ');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B87555156B3CA4B');
        $this->addSql('ALTER TABLE activite CHANGE disponibiliteActivite disponibiliteActivite TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT `fk_activite_user` FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activite RENAME INDEX idx_b87555156b3ca4b TO fk_activite_user');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF06B3CA4B');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0EBD67F4E');
        $this->addSql('ALTER TABLE avis CHANGE commentaire commentaire TEXT NOT NULL, CHANGE date_avis date_avis DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_activite` FOREIGN KEY (idActivite) REFERENCES activite (idActivite) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_user` FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis RENAME INDEX idx_8f91abf0ebd67f4e TO fk_avis_activite');
        $this->addSql('ALTER TABLE avis RENAME INDEX idx_8f91abf06b3ca4b TO fk_avis_user');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE6064046B3CA4B');
        $this->addSql('ALTER TABLE reclamation DROP reponse_admin, DROP date_reponse, CHANGE description description TEXT NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE statut statut ENUM(\'En attente\', \'En cours\', \'Traité\') DEFAULT \'En attente\', CHANGE priorite priorite ENUM(\'Faible\', \'Moyenne\', \'Élevée\') DEFAULT \'Moyenne\'');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT `fk_reclamation_user` FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation RENAME INDEX idx_ce6064046b3ca4b TO fk_reclamation_user');
        $this->addSql('ALTER TABLE user CHANGE photo_profil photo_profil VARCHAR(255) DEFAULT \'default.jpg\', CHANGE role role ENUM(\'admin\', \'voyageur\') DEFAULT \'voyageur\', CHANGE statut statut ENUM(\'actif\', \'inactif\') DEFAULT \'actif\', CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO email');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649f85e0677 TO username');
    }
}
