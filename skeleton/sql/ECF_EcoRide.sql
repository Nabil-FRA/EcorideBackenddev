USEUtilisateurs EcoRide;

-- Table des utilisateurs
CREATE TABLE Utilisateurs (
    utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(50) NOT NULL,
    telephone VARCHAR(50),
    adresse VARCHAR(50),
    date_naissance VARCHAR(50),
    photo BLOB,
    pseudo VARCHAR(50) UNIQUE NOT NULL
);

-- Table des rôles
CREATE TABLE Role (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

-- Relation utilisateur-roles (possède)
CREATE TABLE Possede (
    utilisateur_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (utilisateur_id, role_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES Role(role_id) ON DELETE CASCADE
);

-- Table des véhicules
CREATE TABLE Voiture (
    voiture_id INT AUTO_INCREMENT PRIMARY KEY,
    modele VARCHAR(50) NOT NULL,
    immatriculation VARCHAR(50) UNIQUE NOT NULL,
    energie VARCHAR(50) NOT NULL,
    couleur VARCHAR(50),
    date_premiere_immatriculation VARCHAR(50)
);

-- Relation utilisateur-voitures (gère)
CREATE TABLE Gere (
    utilisateur_id INT NOT NULL,
    voiture_id INT NOT NULL,
    PRIMARY KEY (utilisateur_id, voiture_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (voiture_id) REFERENCES Voiture(voiture_id) ON DELETE CASCADE
);

-- Table des covoiturages
CREATE TABLE Covoiturage (
    covoiturage_id INT AUTO_INCREMENT PRIMARY KEY,
    date_depart DATE NOT NULL,
    heure_depart VARCHAR(50) NOT NULL,
    lieu_depart VARCHAR(50) NOT NULL,
    date_arrivee DATE NOT NULL,
    heure_arrivee VARCHAR(50) NOT NULL,
    lieu_arrivee VARCHAR(50) NOT NULL,
    statut VARCHAR(50),
    nb_place INT NOT NULL,
    prix_personne FLOAT NOT NULL
);

-- Relation covoiturage-voiture (utilise)
CREATE TABLE Utilise (
    covoiturage_id INT NOT NULL,
    voiture_id INT NOT NULL,
    PRIMARY KEY (covoiturage_id, voiture_id),
    FOREIGN KEY (covoiturage_id) REFERENCES Covoiturage(covoiturage_id) ON DELETE CASCADE,
    FOREIGN KEY (voiture_id) REFERENCES Voiture(voiture_id) ON DELETE CASCADE
);

-- Relation covoiturage-utilisateur (participe)
CREATE TABLE Participe (
    covoiturage_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    PRIMARY KEY (covoiturage_id, utilisateur_id),
    FOREIGN KEY (covoiturage_id) REFERENCES Covoiturage(covoiturage_id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id) ON DELETE CASCADE
);

-- Table des avis
CREATE TABLE Avis (
    avis_id INT AUTO_INCREMENT PRIMARY KEY,
    commentaire VARCHAR(50),
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    statut VARCHAR(50)
);

-- Relation utilisateur-avis (dépose)
CREATE TABLE Depose (
    utilisateur_id INT NOT NULL,
    avis_id INT NOT NULL,
    PRIMARY KEY (utilisateur_id, avis_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id) ON DELETE CASCADE,
    FOREIGN KEY (avis_id) REFERENCES Avis(avis_id) ON DELETE CASCADE
);

-- Table des marques de voitures
CREATE TABLE Marque (
    marque_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

-- Relation voiture-marque (détient)
CREATE TABLE Detient (
    voiture_id INT NOT NULL,
    marque_id INT NOT NULL,
    PRIMARY KEY (voiture_id, marque_id),
    FOREIGN KEY (voiture_id) REFERENCES Voiture(voiture_id) ON DELETE CASCADE,
    FOREIGN KEY (marque_id) REFERENCES Marque(marque_id) ON DELETE CASCADE
);

-- Table des configurations
CREATE TABLE Configuration (
    id_configuration INT AUTO_INCREMENT PRIMARY KEY
);

-- Table des paramètres
CREATE TABLE Parametre (
    parametre_id INT AUTO_INCREMENT PRIMARY KEY,
    propriete VARCHAR(50) NOT NULL,
    valeur VARCHAR(50) NOT NULL
);

-- Relation configuration-paramètres (dispose)
CREATE TABLE Dispose (
    id_configuration INT NOT NULL,
    parametre_id INT NOT NULL,
    PRIMARY KEY (id_configuration, parametre_id),
    FOREIGN KEY (id_configuration) REFERENCES Configuration(id_configuration) ON DELETE CASCADE,
    FOREIGN KEY (parametre_id) REFERENCES Parametre(parametre_id) ON DELETE CASCADE
);
