<?php

namespace App\Controller;

use App\Entity\Marque;
use App\Repository\MarqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[Route('/api/marque')]
#[OA\Tag(name: 'Marques')]
class MarqueController extends AbstractController
{
    /**
     * Liste toutes les marques.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste de toutes les marques de véhicules.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'libelle', type: 'string', example: 'Peugeot')
            ])
        )
    )]
    #[Route(name: 'app_marque_index', methods: ['GET'])]
    public function index(MarqueRepository $marqueRepository): JsonResponse
    {
        $marques = $marqueRepository->findAll();
        return $this->json($marques, JsonResponse::HTTP_OK, [], ['groups' => 'marque:read']);
    }

    /**
     * Affiche une marque spécifique par ID.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID de la marque à récupérer", required: true)]
    #[OA\Response(
        response: 200,
        description: "Retourne la marque demandée.",
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'libelle', type: 'string', example: 'Peugeot')
        ])
    )]
    #[OA\Response(response: 404, description: "Marque non trouvée.")]
    #[Route('/{id}', name: 'app_marque_show', methods: ['GET'])]
    public function show(int $id, MarqueRepository $marqueRepository): JsonResponse
    {
        $marque = $marqueRepository->find($id);
        if (!$marque) {
            return new JsonResponse(['message' => 'Marque not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json($marque, JsonResponse::HTTP_OK, [], ['groups' => 'marque:read']);
    }

    /**
     * Crée une nouvelle marque.
     */
    #[OA\RequestBody(
        description: "Données pour la création d'une nouvelle marque",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'libelle', type: 'string', example: 'Tesla')
        ])
    )]
    #[OA\Response(
        response: 201,
        description: "Marque créée avec succès.",
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'id', type: 'integer', example: 3),
            new OA\Property(property: 'libelle', type: 'string', example: 'Tesla')
        ])
    )]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes.")]
    #[Route(name: 'app_marque_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $marque = $serializer->deserialize($request->getContent(), Marque::class, 'json', ['groups' => ['marque:write']]);
            $entityManager->persist($marque);
            $entityManager->flush();
            return $this->json($marque, JsonResponse::HTTP_CREATED, [], ['groups' => 'marque:read']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error: ' . $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Met à jour une marque existante.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID de la marque à mettre à jour", required: true)]
    #[OA\RequestBody(
        description: "Données pour la mise à jour de la marque",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'libelle', type: 'string', example: 'Peugeot Cars')
        ])
    )]
    #[OA\Response(
        response: 200,
        description: "Marque mise à jour avec succès.",
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'libelle', type: 'string', example: 'Peugeot Cars')
        ])
    )]
    #[OA\Response(response: 404, description: "Marque non trouvée.")]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes.")]
    #[Route('/{id}', name: 'app_marque_update', methods: ['PUT'])]
    public function update(int $id, Request $request, SerializerInterface $serializer, MarqueRepository $marqueRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $marque = $marqueRepository->find($id);
        if (!$marque) {
            return new JsonResponse(['message' => 'Marque not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        try {
            $serializer->deserialize($request->getContent(), Marque::class, 'json', ['groups' => ['marque:write'], 'object_to_populate' => $marque]);
            $entityManager->flush();
            return $this->json($marque, JsonResponse::HTTP_OK, [], ['groups' => 'marque:read']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error: ' . $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Supprime une marque.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID de la marque à supprimer", required: true)]
    #[OA\Response(response: 204, description: "Marque supprimée avec succès.")]
    #[OA\Response(response: 404, description: "Marque non trouvée.")]
    #[OA\Response(response: 500, description: "Erreur interne du serveur.")]
    #[Route('/{id}', name: 'app_marque_delete', methods: ['DELETE'])]
    public function delete(int $id, MarqueRepository $marqueRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $marque = $marqueRepository->find($id);
        if (!$marque) {
            return new JsonResponse(['message' => 'Marque not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        try {
            $entityManager->remove($marque);
            $entityManager->flush();
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

