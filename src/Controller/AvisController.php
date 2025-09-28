<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/avis')]
#[OA\Tag(name: 'Avis')]
class AvisController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    /**
     * Récupérer tous les avis.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste de tous les avis.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(example: ['id' => 1, 'note' => 5, 'commentaire' => 'Excellent voyage !'])
        )
    )]
    #[Route(name: 'app_avis_index', methods: ['GET'])]
    public function index(AvisRepository $avisRepository): JsonResponse
    {
        $avis = $avisRepository->findAll();
        $response = $this->serializer->serialize($avis, 'json', ['groups' => 'avis:read']);
        return new JsonResponse($response, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Récupérer un avis spécifique.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID de l'avis", required: true)]
    #[OA\Response(
        response: 200,
        description: "Retourne l'avis demandé.",
        content: new OA\JsonContent(example: ['id' => 1, 'note' => 5, 'commentaire' => 'Excellent voyage !'])
    )]
    #[OA\Response(response: 404, description: "Avis non trouvé.")]
    #[Route('/{id}', name: 'app_avis_show', methods: ['GET'])]
    public function show(Avis $avis): JsonResponse
    {
        $response = $this->serializer->serialize($avis, 'json', ['groups' => 'avis:read']);
        return new JsonResponse($response, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Créer un nouvel avis.
     */
    #[OA\RequestBody(
        description: "Données pour la création d'un nouvel avis",
        required: true,
        content: new OA\JsonContent(example: ['note' => 4, 'commentaire' => 'Trajet agréable.'])
    )]
    #[OA\Response(
        response: 201,
        description: "Avis créé avec succès.",
        content: new OA\JsonContent(example: ['id' => 2, 'note' => 4, 'commentaire' => 'Trajet agréable.'])
    )]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes.")]
    #[Route(name: 'app_avis_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $avis = $this->serializer->deserialize($request->getContent(), Avis::class, 'json');
            $this->entityManager->persist($avis);
            $this->entityManager->flush();
            $location = $this->urlGenerator->generate('app_avis_show', ['id' => $avis->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $response = $this->serializer->serialize($avis, 'json', ['groups' => 'avis:read']);
            return new JsonResponse($response, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid or missing data', 'error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Mettre à jour un avis existant.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID de l'avis à mettre à jour", required: true)]
    #[OA\RequestBody(
        description: "Données pour la mise à jour de l'avis",
        required: true,
        content: new OA\JsonContent(example: ['note' => 5, 'commentaire' => 'Trajet vraiment agréable et ponctuel.'])
    )]
    #[OA\Response(
        response: 200,
        description: "Avis mis à jour avec succès.",
        content: new OA\JsonContent(example: ['id' => 1, 'note' => 5, 'commentaire' => 'Trajet vraiment agréable et ponctuel.'])
    )]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes.")]
    #[OA\Response(response: 404, description: "Avis non trouvé.")]
    #[Route('/{id}', name: 'app_avis_update', methods: ['PUT'])]
    public function update(Request $request, Avis $avis): JsonResponse
    {
        try {
            $this->serializer->deserialize($request->getContent(), Avis::class, 'json', ['object_to_populate' => $avis]);
            $this->entityManager->flush();
            $response = $this->serializer->serialize($avis, 'json', ['groups' => 'avis:read']);
            return new JsonResponse($response, JsonResponse::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid or missing data', 'error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Supprimer un avis.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID de l'avis à supprimer", required: true)]
    #[OA\Response(response: 204, description: "Avis supprimé avec succès.")]
    #[OA\Response(response: 404, description: "Avis non trouvé.")]
    #[Route('/{id}', name: 'app_avis_delete', methods: ['DELETE'])]
    public function delete(Avis $avis): JsonResponse
    {
        $this->entityManager->remove($avis);
        $this->entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
