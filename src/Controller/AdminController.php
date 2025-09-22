<?php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use DoctrineExtensions\Query\Mysql\Date;

class AdminController extends AbstractController
{

    #[Route('/api/admin/utilisateurs', name: 'admin_user_list', methods: ['GET'])]
    public function listUsers(
        UtilisateurRepository $utilisateurRepository,
        Request $request
    ): JsonResponse {
        // 1️⃣ Récupérer les rôles via la relation Possede
        $roles = [];
        foreach ($this->getUser()->getPossedes() as $possede) {
            if ($possede->getRole() && $possede->getRole()->getLibelle()) {
                $roles[] = strtolower(trim($possede->getRole()->getLibelle()));
            }
        }

        // 2️⃣ Vérifier si l'utilisateur connecté est "admin"
        if (!in_array('admin', $roles, true)) {
            return new JsonResponse(['error' => 'Accès réservé aux administrateurs'], 403);
        }

        // 3️⃣ Récupérer tous les utilisateurs
        $users = $utilisateurRepository->findAll();

        // 4️⃣ Formater la liste pour le retour JSON (correction : ajout du 2ᵉ argument à array_map)
        $userList = array_map(function ($user) {
            return [
                'id'     => $user->getId(),
                'nom'    => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email'  => $user->getEmail(),
                'role'   => implode(', ', array_map(
                    function ($p) {
                        return $p->getRole()->getLibelle();
                    },
                    $user->getPossedes()->toArray()
                )),
            ];
        }, $users);
        


        return new JsonResponse($userList);
    }

    #[Route('/api/admin/utilisateur/suspend/{id}', name: 'suspend_utilisateur', methods: ['POST'])]
    public function suspendUtilisateur(int $id, Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $authChecker): JsonResponse
    {
        // 📌 Récupérer les libellés des rôles de l'utilisateur connecté
        $roles = [];
        foreach ($this->getUser()->getPossedes() as $possede) {
            if ($possede->getRole() && $possede->getRole()->getLibelle()) {
                $roles[] = strtolower(trim($possede->getRole()->getLibelle()));
            }
        }

        // 🛡️ Vérifie si l'utilisateur a le rôle admin
        if (!in_array('admin', $roles, true)) {
            return new JsonResponse(['error' => 'Accès réservé aux administrateurs'], 403);
        }

        // 🔍 Recherche de l'utilisateur à suspendre
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        // 📝 Lecture des données JSON fournies
        $data = json_decode($request->getContent(), true);
        $raison = $data['raison'] ?? 'Suspension sans motif';

        // 🚫 Appliquer la suspension
        $utilisateur->setIsActive(false)
                    ->setSuspendedAt(new \DateTime())
                    ->setSuspendReason($raison);
        
        $entityManager->flush();

        // ✅ Confirmation de succès
        return new JsonResponse(['message' => 'Utilisateur suspendu avec succès']);
    }

    #[Route('/api/stats', name: 'statistiques', methods: ['GET'])]
public function index(EntityManagerInterface $entityManager): JsonResponse
{
    // Astuce pour forcer la conversion de la date en chaîne de caractères
    // avant d'appliquer SUBSTRING.
    $covoituragesParJour = $entityManager->createQuery(
        'SELECT SUBSTRING(CONCAT(c.dateDepart, \'\'), 1, 10) as jour, COUNT(c.id) as total 
         FROM App\\Entity\\Covoiturage c 
         GROUP BY jour'
    )->getResult();

    // Même astuce ici, et utilisation de COUNT * 2 pour plus de clarté.
    $creditsParJour = $entityManager->createQuery(
        'SELECT SUBSTRING(CONCAT(c.dateDepart, \'\'), 1, 10) as jour, (COUNT(c.id) * 2) as credits 
         FROM App\\Entity\\Covoiturage c 
         GROUP BY jour'
    )->getResult();

    // --- CORRECTION MAJEURE : Calcul du total des crédits ---
    // On doit compter les utilisateurs et les covoiturages SÉPARÉMENT
    // pour éviter un produit cartésien qui fausse complètement les chiffres.
    
    // 1. Compter le nombre total d'utilisateurs
    $totalUtilisateurs = $entityManager->createQuery(
        'SELECT COUNT(u.id) FROM App\\Entity\\Utilisateur u'
    )->getSingleScalarResult();

    // 2. Compter le nombre total de covoiturages
    $totalCovoiturages = $entityManager->createQuery(
        'SELECT COUNT(c.id) FROM App\\Entity\\Covoiturage c'
    )->getSingleScalarResult();

    // 3. Calculer le total des crédits en PHP
    $totalCredits = ($totalUtilisateurs * 20) - ($totalCovoiturages * 2);

    return new JsonResponse([
        'covoiturages' => $covoituragesParJour,
        'credits' => $creditsParJour,
        'totalCredits' => $totalCredits,
    ]);
}
}
