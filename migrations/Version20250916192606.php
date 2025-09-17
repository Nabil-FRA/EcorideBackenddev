<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250916192606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, depose_id INT DEFAULT NULL, commentaire VARCHAR(50) NOT NULL, note VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, INDEX IDX_8F91ABF041CD8671 (depose_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE configuration (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE covoiturage (id INT AUTO_INCREMENT NOT NULL, date_depart DATE NOT NULL, heure_depart TIME NOT NULL, date_arrivee DATE NOT NULL, heure_arrivee TIME NOT NULL, lieu_depart VARCHAR(50) NOT NULL, lieu_arrivee VARCHAR(50) NOT NULL, nb_place INT NOT NULL, prix_personne DOUBLE PRECISION NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE depose (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_620B87B1FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE detient (id INT AUTO_INCREMENT NOT NULL, voiture_id INT NOT NULL, marque_id INT NOT NULL, UNIQUE INDEX UNIQ_12DA2D20181A8BA (voiture_id), INDEX IDX_12DA2D204827B9B2 (marque_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dispose (id INT AUTO_INCREMENT NOT NULL, parametre_id INT NOT NULL, configuration_id INT NOT NULL, UNIQUE INDEX UNIQ_6262E0666358FF62 (parametre_id), INDEX IDX_6262E06673F32DD8 (configuration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gere (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, voiture_id INT DEFAULT NULL, INDEX IDX_E97897CEFB88E14F (utilisateur_id), UNIQUE INDEX UNIQ_E97897CE181A8BA (voiture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marque (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parametre (id INT AUTO_INCREMENT NOT NULL, propriete VARCHAR(50) NOT NULL, valeur VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parametre_utilisateur (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, configuration_id INT NOT NULL, INDEX IDX_A983BFA6FB88E14F (utilisateur_id), INDEX IDX_A983BFA673F32DD8 (configuration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participe (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, covoiturage_id INT NOT NULL, INDEX IDX_9FFA8D4FB88E14F (utilisateur_id), INDEX IDX_9FFA8D462671590 (covoiturage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE possede (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_3D0B1508FB88E14F (utilisateur_id), INDEX IDX_3D0B1508D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, telephone VARCHAR(50) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, photo LONGBLOB DEFAULT NULL, pseudo VARCHAR(50) DEFAULT NULL, api_token VARCHAR(255) DEFAULT NULL, is_chauffeur TINYINT(1) DEFAULT 0 NOT NULL, is_passager TINYINT(1) DEFAULT 0 NOT NULL, credits INT NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, suspended_at DATETIME DEFAULT NULL, suspend_reason VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilise (id INT AUTO_INCREMENT NOT NULL, voiture_id INT NOT NULL, covoiturage_id INT NOT NULL, INDEX IDX_28917DEF181A8BA (voiture_id), INDEX IDX_28917DEF62671590 (covoiturage_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voiture (id INT AUTO_INCREMENT NOT NULL, modele VARCHAR(50) NOT NULL, immatriculation VARCHAR(50) NOT NULL, energie VARCHAR(50) NOT NULL, couleur VARCHAR(50) NOT NULL, date_premiere_immatriculation DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF041CD8671 FOREIGN KEY (depose_id) REFERENCES depose (id)');
        $this->addSql('ALTER TABLE depose ADD CONSTRAINT FK_620B87B1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE detient ADD CONSTRAINT FK_12DA2D20181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id)');
        $this->addSql('ALTER TABLE detient ADD CONSTRAINT FK_12DA2D204827B9B2 FOREIGN KEY (marque_id) REFERENCES marque (id)');
        $this->addSql('ALTER TABLE dispose ADD CONSTRAINT FK_6262E0666358FF62 FOREIGN KEY (parametre_id) REFERENCES parametre (id)');
        $this->addSql('ALTER TABLE dispose ADD CONSTRAINT FK_6262E06673F32DD8 FOREIGN KEY (configuration_id) REFERENCES configuration (id)');
        $this->addSql('ALTER TABLE gere ADD CONSTRAINT FK_E97897CEFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE gere ADD CONSTRAINT FK_E97897CE181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id)');
        $this->addSql('ALTER TABLE parametre_utilisateur ADD CONSTRAINT FK_A983BFA6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE parametre_utilisateur ADD CONSTRAINT FK_A983BFA673F32DD8 FOREIGN KEY (configuration_id) REFERENCES configuration (id)');
        $this->addSql('ALTER TABLE participe ADD CONSTRAINT FK_9FFA8D4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE participe ADD CONSTRAINT FK_9FFA8D462671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id)');
        $this->addSql('ALTER TABLE possede ADD CONSTRAINT FK_3D0B1508FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE possede ADD CONSTRAINT FK_3D0B1508D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilise ADD CONSTRAINT FK_28917DEF181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilise ADD CONSTRAINT FK_28917DEF62671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF041CD8671');
        $this->addSql('ALTER TABLE depose DROP FOREIGN KEY FK_620B87B1FB88E14F');
        $this->addSql('ALTER TABLE detient DROP FOREIGN KEY FK_12DA2D20181A8BA');
        $this->addSql('ALTER TABLE detient DROP FOREIGN KEY FK_12DA2D204827B9B2');
        $this->addSql('ALTER TABLE dispose DROP FOREIGN KEY FK_6262E0666358FF62');
        $this->addSql('ALTER TABLE dispose DROP FOREIGN KEY FK_6262E06673F32DD8');
        $this->addSql('ALTER TABLE gere DROP FOREIGN KEY FK_E97897CEFB88E14F');
        $this->addSql('ALTER TABLE gere DROP FOREIGN KEY FK_E97897CE181A8BA');
        $this->addSql('ALTER TABLE parametre_utilisateur DROP FOREIGN KEY FK_A983BFA6FB88E14F');
        $this->addSql('ALTER TABLE parametre_utilisateur DROP FOREIGN KEY FK_A983BFA673F32DD8');
        $this->addSql('ALTER TABLE participe DROP FOREIGN KEY FK_9FFA8D4FB88E14F');
        $this->addSql('ALTER TABLE participe DROP FOREIGN KEY FK_9FFA8D462671590');
        $this->addSql('ALTER TABLE possede DROP FOREIGN KEY FK_3D0B1508FB88E14F');
        $this->addSql('ALTER TABLE possede DROP FOREIGN KEY FK_3D0B1508D60322AC');
        $this->addSql('ALTER TABLE utilise DROP FOREIGN KEY FK_28917DEF181A8BA');
        $this->addSql('ALTER TABLE utilise DROP FOREIGN KEY FK_28917DEF62671590');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE configuration');
        $this->addSql('DROP TABLE covoiturage');
        $this->addSql('DROP TABLE depose');
        $this->addSql('DROP TABLE detient');
        $this->addSql('DROP TABLE dispose');
        $this->addSql('DROP TABLE gere');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE parametre');
        $this->addSql('DROP TABLE parametre_utilisateur');
        $this->addSql('DROP TABLE participe');
        $this->addSql('DROP TABLE possede');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE utilise');
        $this->addSql('DROP TABLE voiture');
    }
}
