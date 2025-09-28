<?php

namespace App\Controller;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/roles')]
#[OA\Tag(name: 'Roles')]
class RoleController extends AbstractController
{
    /**
     * Liste tous les rôles.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste de tous les rôles.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'libelle', type: 'string', example: 'client')
            ])
        )
    )]
    #[Route(name: 'app_role_index', methods: ['GET'])]
    public function index(RoleRepository $roleRepository, SerializerInterface $serializer): JsonResponse
    {
        $roles = $roleRepository->findAll();
        $jsonData = $serializer->serialize($roles, 'json', ['groups' => 'role:read']);
        return new JsonResponse($jsonData, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Affiche un rôle spécifique par ID.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID du rôle à récupérer", required: true)]
    #[OA\Response(
        response: 200,
        description: "Retourne le rôle demandé.",
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'libelle', type: 'string', example: 'client')
        ])
    )]
    #[OA\Response(response: 404, description: "Rôle non trouvé.")]
    #[Route('/{id}', name: 'app_role_show', methods: ['GET'])]
    public function show(int $id, RoleRepository $roleRepository, SerializerInterface $serializer): JsonResponse
    {
        $role = $roleRepository->find($id);
        if (!$role) {
            return new JsonResponse(['message' => 'Role not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $jsonData = $serializer->serialize($role, 'json', ['groups' => 'role:read']);
        return new JsonResponse($jsonData, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Crée un nouveau rôle.
     */
    #[OA\RequestBody(
        description: "Données pour la création d'un nouveau rôle",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'libelle', type: 'string', example: 'moderateur')
        ])
    )]
    #[OA\Response(response: 201, description: "Rôle créé avec succès.")]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes.")]
    #[Route(name: 'app_role_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        try {
            $role = $serializer->deserialize($request->getContent(), Role::class, 'json');
            $entityManager->persist($role);
            $entityManager->flush();
            $location = $urlGenerator->generate('app_role_show', ['id' => $role->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $jsonData = $serializer->serialize($role, 'json', ['groups' => 'role:read']);
            return new JsonResponse($jsonData, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid or missing data'], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Met à jour un rôle existant.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID du rôle à mettre à jour", required: true)]
    #[OA\RequestBody(
        description: "Données pour la mise à jour du rôle",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'libelle', type: 'string', example: 'super_admin')
        ])
    )]
    #[OA\Response(response: 200, description: "Rôle mis à jour avec succès.")]
    #[OA\Response(response: 404, description: "Rôle non trouvé.")]
    #[OA\Response(response: 400, description: "Données invalides ou manquantes.")]
    #[Route('/{id}', name: 'app_role_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $role = $roleRepository->find($id);
        if (!$role) {
            return new JsonResponse(['message' => 'Role not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        try {
            $serializer->deserialize($request->getContent(), Role::class, 'json', ['object_to_populate' => $role]);
            $entityManager->flush();
            $jsonData = $serializer->serialize($role, 'json', ['groups' => 'role:read']);
            return new JsonResponse($jsonData, JsonResponse::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid or missing data'], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Supprime un rôle.
     */
    #[OA\Parameter(name: 'id', in: 'path', description: "L'ID du rôle à supprimer", required: true)]
    #[OA\Response(response: 204, description: "Rôle supprimé avec succès.")]
    #[OA\Response(response: 404, description: "Rôle non trouvé.")]
    #[Route('/{id}', name: 'app_role_delete', methods: ['DELETE'])]
    public function delete(int $id, RoleRepository $roleRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $role = $roleRepository->find($id);
        if (!$role) {
            return new JsonResponse(['message' => 'Role not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        try {
            $entityManager->remove($role);
            $entityManager->flush();
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
