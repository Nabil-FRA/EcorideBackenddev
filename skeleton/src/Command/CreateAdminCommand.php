<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Entity\Possede;
use App\Entity\Role;
use App\Entity\Voiture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur.'
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de l\'administrateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe de l\'administrateur');
    }

    private function generateApiToken(): string
    {
        return bin2hex(random_bytes(32)); // Génère un token aléatoire de 64 caractères
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        // Vérifier si un utilisateur avec cet email existe déjà
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);
        $existingUser = $userRepo->findOneBy(['email' => $email]);

        if ($existingUser) {
            $output->writeln('<error>Un utilisateur avec cet email existe déjà.</error>');
            return Command::FAILURE;
        }

        // Vérifier si le rôle ADMIN existe
        $roleRepo = $this->entityManager->getRepository(Role::class);
        $adminRole = $roleRepo->findOneBy(['libelle' => 'ROLE_ADMIN']);
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setLibelle('ROLE_ADMIN');
            $this->entityManager->persist($adminRole);
        }

        // Vérifier s'il existe une voiture à associer, sinon en créer une
        $voitureRepo = $this->entityManager->getRepository(Voiture::class);
        $voiture = $voitureRepo->findOneBy([]);
        if (!$voiture) {
            $voiture = new Voiture();
            $voiture->setModele('AdminCar')
                ->setImmatriculation('ADMIN-001')
                ->setEnergie('Électrique')
                ->setCouleur('Blanc')
                ->setDatePremiereImmatriculation(new \DateTimeImmutable());
            $this->entityManager->persist($voiture);
        }

        // Créer l'utilisateur
        $admin = new Utilisateur();
        $admin->setEmail($email);
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));

        // Générer et attribuer un API token
        $apiToken = $this->generateApiToken();
        $admin->setApiToken($apiToken);

        // Associer le rôle ADMIN
        $possede = new Possede();
        $possede->setUtilisateur($admin);
        $possede->setRole($adminRole);
        $possede->setVoiture($voiture); // Associer une voiture valide

        // Persister les données
        $this->entityManager->persist($admin);
        $this->entityManager->persist($possede);
        $this->entityManager->flush();

        $output->writeln('<info>Administrateur créé avec succès.</info>');
        $output->writeln("<info>API Token : {$apiToken}</info>");

        return Command::SUCCESS;
    }
}
