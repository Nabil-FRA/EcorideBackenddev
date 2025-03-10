<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Possede;
use App\Entity\Role;
use App\Repository\UtilisateurRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

#[Route('/api', name: 'api_')]
class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
    
        // Vérification des champs obligatoires
        if (
            !$data ||
            !isset($data['nom'], $data['prenom'], $data['email'], $data['password']) ||
            empty($data['nom']) ||
            empty($data['prenom']) ||
            empty($data['email']) ||
            empty($data['password'])
        ) {
            return new JsonResponse(['message' => 'Données invalides. Tous les champs sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['message' => 'Un utilisateur avec cet email existe déjà.'], JsonResponse::HTTP_CONFLICT);
        }
    
        // Création de l'utilisateur
        $utilisateur = new Utilisateur();
        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setEmail($data['email']);
        $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $data['password']));
        $utilisateur->setApiToken(bin2hex(random_bytes(32)));
        $utilisateur->setCredits(20); 
    
        // Attribution du rôle : 'employee' (si précisé) sinon 'client' par défaut
        $allowedRoles = ['admin', 'employee', 'client'];
        $requestedRole = strtolower($data['role'] ?? 'client'); // Rôle par défaut = client
    
        if (!in_array($requestedRole, $allowedRoles)) {
            return new JsonResponse(['message' => 'Rôle non autorisé.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Vérification ou création du rôle dans la base de données
        $role = $roleRepository->findOneBy(['libelle' => $requestedRole]);
        if (!$role) {
            $role = new Role();
            $role->setLibelle($requestedRole);
            $this->entityManager->persist($role);
            $this->entityManager->flush();
        }
    
        // Association utilisateur <-> rôle via la table `possede`
        $possede = new Possede();
        $possede->setUtilisateur($utilisateur);
        $possede->setRole($role);
    
        // Sauvegarde des données
        $this->entityManager->persist($utilisateur);
        $this->entityManager->persist($possede);
        $this->entityManager->flush();
    
        return new JsonResponse([
            'message' => ucfirst($requestedRole) . ' créé avec succès',
            'email' => $utilisateur->getEmail(),
            'role' => $role->getLibelle(),  // Retourne le rôle simplifié
            'apiToken' => $utilisateur->getApiToken(),
            'credits' => $utilisateur->getCredits(),
        ], JsonResponse::HTTP_CREATED);
    }
    

    
    
    #[Route('/login', name: 'api_login', methods: ['POST'])]
public function login(
    Request $request,
    UtilisateurRepository $utilisateurRepository,
    UserPasswordHasherInterface $passwordHasher
): JsonResponse {
    $data = json_decode($request->getContent(), true);

    if (!$data || !isset($data['email'], $data['password'])) {
        return new JsonResponse(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // 📌 Rechercher l'utilisateur par email
    $utilisateur = $utilisateurRepository->findOneBy(['email' => $data['email']]);
    if (!$utilisateur) {
        return new JsonResponse(['message' => 'Utilisateur inconnu'], JsonResponse::HTTP_NOT_FOUND);
    }

     // 🚫 Vérifie si l'utilisateur est actif
     if (!$utilisateur->isActive()) {
        return new JsonResponse(['message' => 'Votre compte est désactivé. Contactez l\'administrateur.'], JsonResponse::HTTP_FORBIDDEN);
    }

    // 📌 Vérifier le mot de passe
    if (!$passwordHasher->isPasswordValid($utilisateur, $data['password'])) {
        return new JsonResponse(['message' => 'Invalid credentials'], JsonResponse::HTTP_UNAUTHORIZED);
    }

    // 📌 Récupérer les rôles liés à l'utilisateur
    $roles = [];
    foreach ($utilisateur->getPossedes() as $possede) {
        $roles[] = $possede->getRole()->getLibelle();
    }

    if (empty($roles)) {
        return new JsonResponse(['message' => 'Aucun rôle attribué à cet utilisateur'], JsonResponse::HTTP_FORBIDDEN);
    }

    // 📌 ✅ NE PAS REGÉNÉRER LE TOKEN SI L'UTILISATEUR EN A DÉJÀ UN
    if (!$utilisateur->getApiToken()) {
        $newApiToken = bin2hex(random_bytes(32));
        $utilisateur->setApiToken($newApiToken);
        $this->entityManager->flush();
        error_log("✅ Nouveau Token généré : " . $newApiToken);
    } else {
        $newApiToken = $utilisateur->getApiToken();
        error_log("📌 Utilisation du Token existant : " . $newApiToken);
    }

    return new JsonResponse([
        'email' => $utilisateur->getEmail(),
        'apiToken' => $newApiToken, // ✅ Retourne le token existant ou généré
        'role' => $roles[0],
    ], JsonResponse::HTTP_OK);
}

    


    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
public function resetPassword(Request $request, UtilisateurRepository $utilisateurRepository, \Swift_Mailer $mailer): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    if (!isset($data['email']) || empty($data['email'])) {
        return new JsonResponse(['message' => 'Email requis'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $utilisateur = $utilisateurRepository->findOneBy(['email' => $data['email']]);
    if (!$utilisateur) {
        return new JsonResponse(['message' => 'Utilisateur introuvable'], JsonResponse::HTTP_NOT_FOUND);
    }

    $resetToken = bin2hex(random_bytes(32));
    $utilisateur->setResetToken($resetToken);
    $utilisateur->setResetTokenExpiration(new \DateTime('+1 hour'));

    $this->entityManager->persist($utilisateur);
    $this->entityManager->flush();

    $message = (new \Swift_Message('Réinitialisation de votre mot de passe'))
        ->setFrom('noreply@ecoride.fr')
        ->setTo($utilisateur->getEmail())
        ->setBody(
            $this->renderView(
                'emails/reset_password.html.twig',
                ['resetToken' => $resetToken]
            ),
            'text/html'
        );

    $mailer->send($message);

    return new JsonResponse(['message' => 'Un email de réinitialisation a été envoyé.'], JsonResponse::HTTP_OK);
}

}