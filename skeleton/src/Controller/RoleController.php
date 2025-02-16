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

#[Route('/api/roles')]
class RoleController extends AbstractController
{
    /**
     * Liste tous les rôles.
     */
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

            return new JsonResponse(['message' => 'Role deleted successfully'], JsonResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
