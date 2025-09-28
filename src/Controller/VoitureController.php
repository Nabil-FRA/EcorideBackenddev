<?php

namespace App\Controller;

use App\Repository\GereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Gere;
use OpenApi\Attributes as OA; // Ajout de l'import pour OpenAPI

#[Route('/api/voitures')]
class VoitureController extends AbstractController
{
    /**
     * Récupère la liste des voitures de l'utilisateur connecté.
     */
    #[OA\Tag(name: "Voiture")]
    #[OA\Response(
        response: 200,
        description: "Retourne la liste des voitures de l'utilisateur.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'marque', type: 'string', example: 'Renault'),
                new OA\Property(property: 'modele', type: 'string', example: 'Clio'),
                new OA\Property(property: 'energie', type: 'string', example: 'Essence')
            ])
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Utilisateur non authentifié."
    )]
    #[OA\Response(
        response: 404,
        description: "Aucune voiture trouvée pour cet utilisateur."
    )]
    #[OA\Security(name: "Bearer")] // Indique que cette route nécessite une authentification
    #[Route('/utilisateur', name: 'get_voitures_utilisateur', methods: ['GET'])]
    public function getVoituresUtilisateur(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return new JsonResponse(['message' => 'Utilisateur non connecté'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Récupérer les relations entre l'utilisateur et ses voitures via la table Gere
        $relations = $entityManager->getRepository(Gere::class)->findBy(['utilisateur' => $user]);

        if (empty($relations)) {
            return new JsonResponse(['message' => 'Aucune voiture trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Construire la réponse JSON
        $voitures = array_map(function (Gere $gere) {
            $voiture = $gere->getVoiture();
            $marque = $voiture->getDetient() ? ($voiture->getDetient()->getMarque() ? $voiture->getDetient()->getMarque()->getLibelle() : 'Marque inconnue') : 'Marque inconnue';

            return [
                'id' => $voiture->getId(),
                'marque' => $marque,
                'modele' => $voiture->getModele(),
                'energie' => $voiture->getEnergie()
            ];
        }, $relations);

        return new JsonResponse($voitures, JsonResponse::HTTP_OK);
    }
}