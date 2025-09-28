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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin')]
class AdminController extends AbstractController
{
    /**
     * Liste tous les utilisateurs (admin seulement).
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste de tous les utilisateurs.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(example: [
                'id' => 1,
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'jean.dupont@example.com',
                'role' => 'client'
            ])
        )
    )]
    #[OA\Response(response: 403, description: "Accès refusé, réservé aux administrateurs.")]
    #[Route('/api/admin/utilisateurs', name: 'admin_user_list', methods: ['GET'])]
    public function listUsers(UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $roles = [];
        foreach ($this->getUser()->getPossedes() as $possede) {
            if ($possede->getRole() && $possede->getRole()->getLibelle()) {
                $roles[] = strtolower(trim($possede->getRole()->getLibelle()));
            }
        }

        if (!in_array('admin', $roles, true)) {
            return new JsonResponse(['error' => 'Accès réservé aux administrateurs'], 403);
        }

        $users = $utilisateurRepository->findAll();
        $userList = array_map(function ($user) {
            return [
                'id'     => $user->getId(),
                'nom'    => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email'  => $user->getEmail(),
                'role'   => implode(', ', array_map(fn($p) => $p->getRole()->getLibelle(), $user->getPossedes()->toArray())),
            ];
        }, $users);

        return new JsonResponse($userList);
    }

    /**
     * Suspend un utilisateur (admin seulement).
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID de l'utilisateur à suspendre", required: true)]
    #[OA\RequestBody(
        description: "Raison de la suspension",
        required: true,
        content: new OA\JsonContent(example: ['raison' => 'Non-respect des règles de la communauté.'])
    )]
    #[OA\Response(response: 200, description: "Utilisateur suspendu avec succès.")]
    #[OA\Response(response: 403, description: "Accès refusé, réservé aux administrateurs.")]
    #[OA\Response(response: 404, description: "Utilisateur non trouvé.")]
    #[Route('/api/admin/utilisateur/suspend/{id}', name: 'suspend_utilisateur', methods: ['POST'])]
    public function suspendUtilisateur(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $roles = [];
        foreach ($this->getUser()->getPossedes() as $possede) {
            if ($possede->getRole() && $possede->getRole()->getLibelle()) {
                $roles[] = strtolower(trim($possede->getRole()->getLibelle()));
            }
        }

        if (!in_array('admin', $roles, true)) {
            return new JsonResponse(['error' => 'Accès réservé aux administrateurs'], 403);
        }

        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$utilisateur) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $raison = $data['raison'] ?? 'Suspension sans motif';

        $utilisateur->setIsActive(false)
                    ->setSuspendedAt(new \DateTime())
                    ->setSuspendReason($raison);
        
        $entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur suspendu avec succès']);
    }

    /**
     * Récupère les statistiques globales de l'application.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne les statistiques sur les covoiturages et les crédits.",
        content: new OA\JsonContent(example: [
            'covoiturages' => [['jour' => '2023-10-01', 'total' => 5]],
            'credits' => [['jour' => '2023-10-01', 'credits' => 10]],
            'totalCredits' => 1990
        ])
    )]
    #[Route('/api/stats', name: 'statistiques', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $covoituragesParJour = $entityManager->createQuery(
            'SELECT SUBSTRING(CONCAT(c.dateDepart, \'\'), 1, 10) as jour, COUNT(c.id) as total 
             FROM App\\Entity\\Covoiturage c 
             GROUP BY jour'
        )->getResult();

        $creditsParJour = $entityManager->createQuery(
            'SELECT SUBSTRING(CONCAT(c.dateDepart, \'\'), 1, 10) as jour, (COUNT(c.id) * 2) as credits 
             FROM App\\Entity\\Covoiturage c 
             GROUP BY jour'
        )->getResult();
        
        $totalUtilisateurs = $entityManager->createQuery('SELECT COUNT(u.id) FROM App\\Entity\\Utilisateur u')->getSingleScalarResult();
        $totalCovoiturages = $entityManager->createQuery('SELECT COUNT(c.id) FROM App\\Entity\\Covoiturage c')->getSingleScalarResult();
        $totalCredits = ($totalUtilisateurs * 20) - ($totalCovoiturages * 2);

        return new JsonResponse([
            'covoiturages' => $covoituragesParJour,
            'credits' => $creditsParJour,
            'totalCredits' => $totalCredits,
        ]);
    }
}
