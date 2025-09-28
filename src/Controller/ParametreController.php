<?php

namespace App\Controller;

use App\Entity\Parametre;
use App\Repository\ParametreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;

#[Route('/api/parametre')]
#[OA\Tag(name: 'Parametres')]
class ParametreController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ParametreRepository $parametreRepository;
    private SerializerInterface $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParametreRepository $parametreRepository,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->parametreRepository = $parametreRepository;
        $this->serializer = $serializer;
    }

    /**
     * Liste tous les paramètres.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste de tous les paramètres.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'propriete', type: 'string', example: 'Fumeur'),
                new OA\Property(property: 'valeur', type: 'string', example: 'Non')
            ])
        )
    )]
    #[Route('/', name: 'app_parametre_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $parametres = $this->parametreRepository->findAll();
        $data = $this->serializer->serialize($parametres, 'json', ['groups' => 'parametre:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    /**
     * Affiche un paramètre spécifique par ID.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID du paramètre", required: true)]
    #[OA\Response(
        response: 200,
        description: "Retourne le paramètre demandé.",
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'propriete', type: 'string', example: 'Fumeur'),
            new OA\Property(property: 'valeur', type: 'string', example: 'Non')
        ])
    )]
    #[OA\Response(response: 404, description: "Paramètre non trouvé.")]
    #[Route('/{id}', name: 'app_parametre_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $parametre = $this->parametreRepository->find($id);

        if (!$parametre) {
            return new JsonResponse(['message' => 'Parametre not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($parametre, 'json', ['groups' => 'parametre:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    /**
     * Crée un nouveau paramètre.
     */
    #[OA\RequestBody(
        description: "Données pour la création d'un nouveau paramètre.",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'propriete', type: 'string', example: 'Animaux'),
            new OA\Property(property: 'valeur', type: 'string', example: 'Acceptés')
        ])
    )]
    #[OA\Response(
        response: 201,
        description: "Paramètre créé avec succès.",
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'id', type: 'integer', example: 3),
            new OA\Property(property: 'propriete', type: 'string', example: 'Animaux'),
            new OA\Property(property: 'valeur', type: 'string', example: 'Acceptés')
        ])
    )]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes.")]
    #[Route('/', name: 'app_parametre_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $parametre = new Parametre();
        $parametre->setPropriete($data['propriete'] ?? '');
        $parametre->setValeur($data['valeur'] ?? '');

        $this->entityManager->persist($parametre);
        $this->entityManager->flush();

        $response = $this->serializer->serialize($parametre, 'json', ['groups' => 'parametre:read']);
        return new JsonResponse($response, Response::HTTP_CREATED, [], true);
    }

    /**
     * Met à jour un paramètre existant.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID du paramètre à mettre à jour", required: true)]
    #[OA\RequestBody(
        description: "Données pour la mise à jour du paramètre.",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'valeur', type: 'string', example: 'Refusés')
        ])
    )]
    #[OA\Response(response: 200, description: "Paramètre mis à jour avec succès.")]
    #[OA\Response(response: 404, description: "Paramètre non trouvé.")]
    #[Route('/{id}', name: 'app_parametre_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $parametre = $this->parametreRepository->find($id);

        if (!$parametre) {
            return new JsonResponse(['message' => 'Parametre not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $parametre->setPropriete($data['propriete'] ?? $parametre->getPropriete());
        $parametre->setValeur($data['valeur'] ?? $parametre->getValeur());

        $this->entityManager->flush();

        $response = $this->serializer->serialize($parametre, 'json', ['groups' => 'parametre:read']);
        return new JsonResponse($response, Response::HTTP_OK, [], true);
    }

    /**
     * Supprime un paramètre.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID du paramètre à supprimer", required: true)]
    #[OA\Response(response: 204, description: "Paramètre supprimé avec succès.")]
    #[OA\Response(response: 404, description: "Paramètre non trouvé.")]
    #[Route('/{id}', name: 'app_parametre_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $parametre = $this->parametreRepository->find($id);

        if (!$parametre) {
            return new JsonResponse(['message' => 'Parametre not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($parametre);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
