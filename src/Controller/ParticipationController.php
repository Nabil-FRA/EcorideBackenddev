<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Participe;
use App\Entity\Utilisateur;
use App\Service\MongoDBService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/covoiturage')]
#[OA\Tag(name: "Participation")]
class ParticipationController extends AbstractController
{
    /**
     * Permet à un utilisateur de participer à un covoiturage.
     */
    #[OA\Security(name: "Bearer")]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: "L'ID du covoiturage auquel participer.",
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'confirm',
        in: 'query',
        description: "Mettre à 'true' ou '1' pour confirmer la participation et débiter les crédits. Sans ce paramètre, la route renvoie un message de confirmation.",
        required: false,
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Response(
        response: 200,
        description: "Participation confirmée avec succès.",
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'message', type: 'string', example: 'Participation confirmée et enregistrée dans MongoDB'),
            new OA\Property(property: 'creditsRestants', type: 'integer', example: 18)
        ])
    )]
    #[OA\Response(response: 202, description: "Confirmation requise. L'utilisateur doit rappeler l'API avec le paramètre `?confirm=true`.")]
    #[OA\Response(response: 400, description: "Plus de places disponibles.")]
    #[OA\Response(response: 401, description: "Utilisateur non authentifié.")]
    #[OA\Response(response: 402, description: "Crédits insuffisants.")]
    #[OA\Response(response: 403, description: "Seuls les passagers peuvent participer.")]
    #[OA\Response(response: 404, description: "Covoiturage introuvable.")]
    #[OA\Response(response: 409, description: "L'utilisateur participe déjà à ce covoiturage.")]
    #[Route('/{id}/participer', name: 'covoiturage_participer', methods: ['POST'])]
    public function participer(int $id, EntityManagerInterface $entityManager, Request $request, MongoDBService $mongoDBService): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return $this->json(['message' => 'Veuillez vous connecter pour participer'], Response::HTTP_UNAUTHORIZED);
        }

        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($utilisateur->getId());

        if (!$utilisateur->isPassager()) {
            return $this->json(['message' => 'Seuls les passagers peuvent participer'], Response::HTTP_FORBIDDEN);
        }

        $covoiturage = $entityManager->getRepository(Covoiturage::class)->find($id);
        if (!$covoiturage) {
            return $this->json(['message' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }
        
        // CORRECTION 1 : Vérifier si l'utilisateur participe déjà à ce covoiturage
        $participationExistante = $entityManager->getRepository(Participe::class)->findOneBy([
            'utilisateur' => $utilisateur,
            'covoiturage' => $covoiturage
        ]);

        if ($participationExistante) {
            return $this->json(['message' => 'Vous participez déjà à ce covoiturage'], Response::HTTP_CONFLICT); // 409 Conflict
        }

        if ($covoiturage->getNbPlace() <= 0) {
            return $this->json(['message' => 'Plus de places disponibles'], Response::HTTP_BAD_REQUEST);
        }

        if ($utilisateur->getCredits() < 2) {
            return $this->json(['message' => 'Vous devez avoir au moins 2 crédits pour participer'], Response::HTTP_PAYMENT_REQUIRED);
        }

        if (!$request->query->get('confirm')) {
            return $this->json(['message' => 'Veuillez confirmer votre participation'], Response::HTTP_ACCEPTED);
        }

        $nouveauSolde = $utilisateur->getCredits() - 2;
        $utilisateur->setCredits($nouveauSolde);
        $covoiturage->setNbPlace($covoiturage->getNbPlace() - 1);

        $participation = new Participe();
        $participation->setUtilisateur($utilisateur);
        $participation->setCovoiturage($covoiturage);
        $entityManager->persist($participation);
        $entityManager->flush();

        $db = $mongoDBService->getDatabase();
        $collection = $db->selectCollection('participations');

        // CORRECTION 2 : Vérifier que les objets DateTime ne sont pas null avant d'appeler format()
        $participationData = [
            'utilisateur' => [
                'id' => $utilisateur->getId(),
                'nom' => $utilisateur->getNom(),
                'prenom' => $utilisateur->getPrenom(),
                'email' => $utilisateur->getEmail(),
            ],
            'covoiturage' => [
                'id' => $covoiturage->getId(),
                'lieuDepart' => $covoiturage->getLieuDepart(),
                'lieuArrivee' => $covoiturage->getLieuArrivee(),
                'dateDepart' => $covoiturage->getDateDepart() ? $covoiturage->getDateDepart()->format('Y-m-d') : null,
                'heureDepart' => $covoiturage->getHeureDepart() ? $covoiturage->getHeureDepart()->format('H:i:s') : null,
                // On peut aussi ajouter le prix pour l'historique MongoDB
                'prixPersonne' => $covoiturage->getPrixPersonne() 
            ],
            'creditsUtilises' => 2,
            'dateParticipation' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        $collection->insertOne($participationData);

        return $this->json([
            'message' => 'Participation confirmée et enregistrée dans MongoDB',
            'creditsRestants' => $utilisateur->getCredits()
        ], Response::HTTP_OK);
    }
}