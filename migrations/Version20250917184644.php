<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917184644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id SERIAL NOT NULL, depose_id INT DEFAULT NULL, commentaire VARCHAR(50) NOT NULL, note VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8F91ABF041CD8671 ON avis (depose_id)');
        $this->addSql('CREATE TABLE configuration (id SERIAL NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE covoiturage (id SERIAL NOT NULL, date_depart DATE NOT NULL, heure_depart TIME(0) WITHOUT TIME ZONE NOT NULL, date_arrivee DATE NOT NULL, heure_arrivee TIME(0) WITHOUT TIME ZONE NOT NULL, lieu_depart VARCHAR(50) NOT NULL, lieu_arrivee VARCHAR(50) NOT NULL, nb_place INT NOT NULL, prix_personne DOUBLE PRECISION NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE depose (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_620B87B1FB88E14F ON depose (utilisateur_id)');
        $this->addSql('CREATE TABLE detient (id SERIAL NOT NULL, voiture_id INT NOT NULL, marque_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_12DA2D20181A8BA ON detient (voiture_id)');
        $this->addSql('CREATE INDEX IDX_12DA2D204827B9B2 ON detient (marque_id)');
        $this->addSql('CREATE TABLE dispose (id SERIAL NOT NULL, parametre_id INT NOT NULL, configuration_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6262E0666358FF62 ON dispose (parametre_id)');
        $this->addSql('CREATE INDEX IDX_6262E06673F32DD8 ON dispose (configuration_id)');
        $this->addSql('CREATE TABLE gere (id SERIAL NOT NULL, utilisateur_id INT DEFAULT NULL, voiture_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E97897CEFB88E14F ON gere (utilisateur_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E97897CE181A8BA ON gere (voiture_id)');
        $this->addSql('CREATE TABLE marque (id SERIAL NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE parametre (id SERIAL NOT NULL, propriete VARCHAR(50) NOT NULL, valeur VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE parametre_utilisateur (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, configuration_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A983BFA6FB88E14F ON parametre_utilisateur (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_A983BFA673F32DD8 ON parametre_utilisateur (configuration_id)');
        $this->addSql('CREATE TABLE participe (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, covoiturage_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9FFA8D4FB88E14F ON participe (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_9FFA8D462671590 ON participe (covoiturage_id)');
        $this->addSql('CREATE TABLE possede (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3D0B1508FB88E14F ON possede (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_3D0B1508D60322AC ON possede (role_id)');
        $this->addSql('CREATE TABLE role (id SERIAL NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE utilisateur (id SERIAL NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, telephone VARCHAR(50) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, photo BYTEA DEFAULT NULL, pseudo VARCHAR(50) DEFAULT NULL, api_token VARCHAR(255) DEFAULT NULL, is_chauffeur BOOLEAN DEFAULT false NOT NULL, is_passager BOOLEAN DEFAULT false NOT NULL, credits INT NOT NULL, is_active BOOLEAN DEFAULT true NOT NULL, suspended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, suspend_reason VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
        $this->addSql('CREATE TABLE utilise (id SERIAL NOT NULL, voiture_id INT NOT NULL, covoiturage_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_28917DEF181A8BA ON utilise (voiture_id)');
        $this->addSql('CREATE INDEX IDX_28917DEF62671590 ON utilise (covoiturage_id)');
        $this->addSql('CREATE TABLE voiture (id SERIAL NOT NULL, modele VARCHAR(50) NOT NULL, immatriculation VARCHAR(50) NOT NULL, energie VARCHAR(50) NOT NULL, couleur VARCHAR(50) NOT NULL, date_premiere_immatriculation TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN voiture.date_premiere_immatriculation IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF041CD8671 FOREIGN KEY (depose_id) REFERENCES depose (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE depose ADD CONSTRAINT FK_620B87B1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE detient ADD CONSTRAINT FK_12DA2D20181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE detient ADD CONSTRAINT FK_12DA2D204827B9B2 FOREIGN KEY (marque_id) REFERENCES marque (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dispose ADD CONSTRAINT FK_6262E0666358FF62 FOREIGN KEY (parametre_id) REFERENCES parametre (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dispose ADD CONSTRAINT FK_6262E06673F32DD8 FOREIGN KEY (configuration_id) REFERENCES configuration (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE gere ADD CONSTRAINT FK_E97897CEFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE gere ADD CONSTRAINT FK_E97897CE181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE parametre_utilisateur ADD CONSTRAINT FK_A983BFA6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE parametre_utilisateur ADD CONSTRAINT FK_A983BFA673F32DD8 FOREIGN KEY (configuration_id) REFERENCES configuration (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participe ADD CONSTRAINT FK_9FFA8D4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participe ADD CONSTRAINT FK_9FFA8D462671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE possede ADD CONSTRAINT FK_3D0B1508FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE possede ADD CONSTRAINT FK_3D0B1508D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE utilise ADD CONSTRAINT FK_28917DEF181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE utilise ADD CONSTRAINT FK_28917DEF62671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8F91ABF041CD8671');
        $this->addSql('ALTER TABLE depose DROP CONSTRAINT FK_620B87B1FB88E14F');
        $this->addSql('ALTER TABLE detient DROP CONSTRAINT FK_12DA2D20181A8BA');
        $this->addSql('ALTER TABLE detient DROP CONSTRAINT FK_12DA2D204827B9B2');
        $this->addSql('ALTER TABLE dispose DROP CONSTRAINT FK_6262E0666358FF62');
        $this->addSql('ALTER TABLE dispose DROP CONSTRAINT FK_6262E06673F32DD8');
        $this->addSql('ALTER TABLE gere DROP CONSTRAINT FK_E97897CEFB88E14F');
        $this->addSql('ALTER TABLE gere DROP CONSTRAINT FK_E97897CE181A8BA');
        $this->addSql('ALTER TABLE parametre_utilisateur DROP CONSTRAINT FK_A983BFA6FB88E14F');
        $this->addSql('ALTER TABLE parametre_utilisateur DROP CONSTRAINT FK_A983BFA673F32DD8');
        $this->addSql('ALTER TABLE participe DROP CONSTRAINT FK_9FFA8D4FB88E14F');
        $this->addSql('ALTER TABLE participe DROP CONSTRAINT FK_9FFA8D462671590');
        $this->addSql('ALTER TABLE possede DROP CONSTRAINT FK_3D0B1508FB88E14F');
        $this->addSql('ALTER TABLE possede DROP CONSTRAINT FK_3D0B1508D60322AC');
        $this->addSql('ALTER TABLE utilise DROP CONSTRAINT FK_28917DEF181A8BA');
        $this->addSql('ALTER TABLE utilise DROP CONSTRAINT FK_28917DEF62671590');
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
