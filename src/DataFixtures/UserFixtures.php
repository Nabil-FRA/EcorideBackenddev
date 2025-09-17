<?php

namespace App\DataFixtures;

use App\Entity\Possede;
use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Création de l'administrateur
        $admin = new Utilisateur();
        $admin->setEmail('admin@ecoride.com');
        $admin->setNom('Admin');
        $admin->setPrenom('EcoRide');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        
        $possedeAdmin = new Possede();
        $possedeAdmin->setUtilisateur($admin);
        // This is likely line 35 - we get the role created in RoleFixtures
        $possedeAdmin->setRole($this->getReference(RoleFixtures::ADMIN_ROLE_REFERENCE));
        $manager->persist($possedeAdmin);
        
        $manager->persist($admin);
        $this->addReference('user-admin', $admin);

        // Création de 10 utilisateurs normaux
        for ($i = 0; $i < 10; $i++) {
            $user = new Utilisateur();
            $user->setEmail($faker->email);
            $user->setNom($faker->lastName);
            $user->setPrenom($faker->firstName);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'userpass'));

            $possedeUser = new Possede();
            $possedeUser->setUtilisateur($user);
            // We get the role created in RoleFixtures
            $possedeUser->setRole($this->getReference(RoleFixtures::USER_ROLE_REFERENCE));
            $manager->persist($possedeUser);

            $manager->persist($user);
            $this->addReference('user-' . $i, $user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }
}