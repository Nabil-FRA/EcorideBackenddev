<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use App\Entity\Role;
use App\Entity\Voiture;
use App\Entity\Covoiturage;
use App\Entity\Participe;
use App\Entity\Utilise;
use App\Entity\Marque;
use App\Entity\Avis;
use App\Entity\Depose;
use App\Entity\Configuration;
use App\Entity\Dispose;
use App\Entity\Parametre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use DateTimeImmutable;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Étape 1 : Créer des rôles
        $roles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MANAGER'];
        foreach ($roles as $roleName) {
            $role = new Role();
            $role->setLibelle($roleName);
            $manager->persist($role);
        }
        $manager->flush();

        // Étape 2 : Créer des utilisateurs
        $utilisateurs = [];
        for ($i = 1; $i <= 10; $i++) {
            $utilisateur = (new Utilisateur())
                ->setNom("Nom_$i")
                ->setPrenom("Prenom_$i")
                ->setEmail("user$i@example.com")
                ->setPassword($this->passwordHasher->hashPassword(new Utilisateur(), "password$i"))
                ->setTelephone("060000000$i")
                ->setAdresse("Adresse $i")
                ->setDateNaissance(new DateTimeImmutable("2000-01-$i"))
                ->setPseudo("Pseudo_$i");

            $manager->persist($utilisateur);
            $utilisateurs[] = $utilisateur;
        }
        $manager->flush();

        // Étape 3 : Créer des marques
        $marques = [];
        for ($i = 1; $i <= 5; $i++) {
            $marque = (new Marque())
                ->setLibelle("Marque_$i");
            $manager->persist($marque);
            $marques[] = $marque;
        }
        $manager->flush();

        // Étape 4 : Créer des voitures
        $voitures = [];
        for ($i = 1; $i <= 10; $i++) {
            $voiture = (new Voiture())
                ->setModele("Modele_$i")
                ->setImmatriculation("AB-123-CD-$i")
                ->setEnergie("Essence")
                ->setCouleur("Couleur_$i")
                ->setDatePremiereImmatriculation(new DateTimeImmutable("2015-01-$i"));
            $manager->persist($voiture);
            $voitures[] = $voiture;
        }
        $manager->flush();

        // Étape 5 : Créer des participes
        $participes = [];
        foreach ($utilisateurs as $utilisateur) {
            $participe = new Participe();
            $participe->setUtilisateur($utilisateur);
            $manager->persist($participe);
            $participes[] = $participe;
        }
        $manager->flush();

        // Étape 6 : Créer des covoiturages
        $covoiturages = [];
        for ($i = 1; $i <= 5; $i++) {
            $covoiturage = (new Covoiturage())
                ->setDateDepart(new DateTimeImmutable("2023-01-$i"))
                ->setHeureDepart(new DateTimeImmutable("1970-01-01 08:00:00"))
                ->setLieuDepart("Lieu départ $i")
                ->setDateArrivee(new DateTimeImmutable("2023-01-$i"))
                ->setHeureArrivee(new DateTimeImmutable("1970-01-01 10:00:00"))
                ->setLieuArrivee("Lieu arrivée $i")
                ->setNbPlace(rand(2, 5))
                ->setPrixPersonne(20.0)
                ->setStatut("Disponible")
                ->setParticipe($participes[array_rand($participes)]);
            $manager->persist($covoiturage);
            $covoiturages[] = $covoiturage;
        }
        $manager->flush();

        // Étape 7 : Créer des relations "Utilise"
        foreach ($voitures as $voiture) {
            foreach ($covoiturages as $covoiturage) {
                $utilise = new Utilise();
                $utilise->setVoiture($voiture);
                $utilise->setCovoiturage($covoiturage);
                $manager->persist($utilise);
            }
        }
        $manager->flush();

        // Étape 8 : Créer des configurations
        $configurations = [];
        for ($i = 1; $i <= 3; $i++) {
            $configuration = new Configuration();
            $manager->persist($configuration);
            $configurations[] = $configuration;
        }
        $manager->flush();

        // Étape 9 : Créer des paramètres et des disposes
        foreach ($configurations as $configuration) {
            for ($i = 1; $i <= 3; $i++) {
                $parametre = (new Parametre())
                    ->setPropriete("Propriété_$i")
                    ->setValeur("Valeur_$i")
                    ->setUtilisateur($utilisateurs[array_rand($utilisateurs)]);
                $manager->persist($parametre);

                $dispose = new Dispose();
                $dispose
                    ->setParametre($parametre)
                    ->setConfiguration($configuration);
                $manager->persist($dispose);
            }
        }
        $manager->flush();

        // Étape 10 : Créer des dépôts et des avis
        foreach ($utilisateurs as $utilisateur) {
            $depose = new Depose();
            $depose->setUtilisateur($utilisateur);
            $manager->persist($depose);

            $avis = new Avis();
            $avis->setCommentaire("Commentaire de l'utilisateur {$utilisateur->getNom()}")
                 ->setNote((string) rand(1, 5))
                 ->setStatut("Validé")
                 ->setDepose($depose);
            $manager->persist($avis);
        }
        $manager->flush();
    }
}
