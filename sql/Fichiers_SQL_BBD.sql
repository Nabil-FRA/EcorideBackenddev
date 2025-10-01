-- Création de la base de données
CREATE DATABASE ecoride CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoride;

-- Table utilisateur
CREATE TABLE utilisateur (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(50) DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    date_naissance DATE DEFAULT NULL,
    photo LONGBLOB DEFAULT NULL,
    pseudo VARCHAR(50) DEFAULT NULL,
    api_token VARCHAR(255) DEFAULT NULL,
    is_chauffeur TINYINT(1) NOT NULL DEFAULT 0,
    is_passager TINYINT(1) NOT NULL DEFAULT 0,
    credits INT(11) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    suspended_at DATETIME DEFAULT NULL,
    suspend_reason VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- Table role
CREATE TABLE role (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- Table possede (relation utilisateur-role)
CREATE TABLE possede (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT(11),
    role_id INT(11),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table covoiturage
CREATE TABLE covoiturage (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    date_arrivee DATE NOT NULL,
    heure_arrivee TIME NOT NULL,
    lieu_depart VARCHAR(100) NOT NULL,
    lieu_arrivee VARCHAR(100) NOT NULL,
    nb_place INT(11) NOT NULL,
    prix_personne DECIMAL(10,2) NOT NULL,
    statut VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- Table voiture
CREATE TABLE voiture (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    modele VARCHAR(50) NOT NULL,
    immatriculation VARCHAR(20) NOT NULL UNIQUE,
    energie VARCHAR(50) NOT NULL,
    couleur VARCHAR(50) NOT NULL,
    date_premiere_immatriculation DATETIME NOT NULL
) ENGINE=InnoDB;

-- Table marque
CREATE TABLE marque (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Table participe
CREATE TABLE participe (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT(11) NOT NULL,
    covoiturage_id INT(11) NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table configuration
CREATE TABLE configuration (
    id INT(11) AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB;

-- Table depose (liaison pour les avis)
CREATE TABLE depose (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT(11) NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table avis
CREATE TABLE avis (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    depose_id INT(11) NOT NULL,
    commentaire TEXT,
    note INT(1) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    -- NOTE : La clé étrangère référence maintenant 'depose(id)' et non plus 'utilisateur(id)'
    FOREIGN KEY (depose_id) REFERENCES depose(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table parametre
CREATE TABLE parametre (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    propriete VARCHAR(100) NOT NULL UNIQUE,
    valeur TEXT NOT NULL
) ENGINE=InnoDB;

-- Table dispose
CREATE TABLE dispose (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    parametre_id INT(11) NOT NULL,
    configuration_id INT(11) NOT NULL,
    FOREIGN KEY (parametre_id) REFERENCES parametre(id) ON DELETE CASCADE,
    FOREIGN KEY (configuration_id) REFERENCES configuration(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table detient
CREATE TABLE detient (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    voiture_id INT(11) NOT NULL,
    marque_id INT(11) NOT NULL,
    FOREIGN KEY (voiture_id) REFERENCES voiture(id) ON DELETE CASCADE,
    FOREIGN KEY (marque_id) REFERENCES marque(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table gere
CREATE TABLE gere (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT(11) NOT NULL,
    voiture_id INT(11) NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (voiture_id) REFERENCES voiture(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table utilise
CREATE TABLE utilise (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    voiture_id INT(11) NOT NULL,
    covoiturage_id INT(11) NOT NULL,
    FOREIGN KEY (voiture_id) REFERENCES voiture(id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturage(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table parametre_utilisateur
CREATE TABLE parametre_utilisateur (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT(11) NOT NULL,
    configuration_id INT(11) NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (configuration_id) REFERENCES configuration(id) ON DELETE CASCADE
) ENGINE=InnoDB;


---------------------------------------------------------Création du Compte Admin-----------------------------------------------------------------------
-- Insertion du role admin
INSERT INTO role (libelle) VALUES ('admin');

-- Insertion d'un compte admin
INSERT INTO utilisateur (nom, prenom, email, password, is_chauffeur, is_passager, credits, is_active)
VALUES ('Admin', 'EcoRide', 'admin@ecoride.com', '$2y$13$WDI/YZoU6aImkjCmxa3XBe4qQKKnm5sggCRJwtoMuL7/WeJfGofRK', 0, 0, 20, 1);

-- Liaison de l'utilisateur admin au role admin
INSERT INTO possede (utilisateur_id, role_id)
VALUES ((SELECT id FROM utilisateur WHERE email = 'admin@ecoride.com'), (SELECT id FROM role WHERE libelle = 'admin'));

---------------------------------------------------------Création du Compte client-----------------------------------------------------------------------

-- Insertion du role client
INSERT INTO role (libelle) VALUES ('client');

-- Insertion d'un compte Client
INSERT INTO utilisateur (nom, prenom, email, password, is_chauffeur, is_passager, credits, is_active)
VALUES ('Client', 'EcoRide', 'Client@ecoride.com', '$2y$13$WDI/YZoU6aImkjCmxa3XBe4qQKKnm5sggCRJwtoMuL7/WeJfGofRK', 0, 0, 20, 1);

-- Liaison de l'utilisateur Client au role Client
INSERT INTO possede (utilisateur_id, role_id)
-- NOTE : L'email a été corrigé pour correspondre exactement à l'enregistrement (C majuscule)
VALUES ((SELECT id FROM utilisateur WHERE email = 'Client@ecoride.com'), (SELECT id FROM role WHERE libelle = 'client'));

---------------------------------------------------------Création du Compte employé-----------------------------------------------------------------------
-- Insertion du role employee
INSERT INTO role (libelle) VALUES ('employee');

-- Insertion d'un compte employee
INSERT INTO utilisateur (nom, prenom, email, password, is_chauffeur, is_passager, credits, is_active)
VALUES ('Employe', 'EcoRide', 'employee@ecoride.com', '$2y$13$WDI/YZoU6aImkjCmxa3XBe4qQKKnm5sggCRJwtoMuL7/WeJfGofRK', 0, 0, 20, 1);

-- Liaison de l'utilisateur employé au role employee
INSERT INTO possede (utilisateur_id, role_id)
VALUES ((SELECT id FROM utilisateur WHERE email = 'employee@ecoride.com'), (SELECT id FROM role WHERE libelle = 'employee'));