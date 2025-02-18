<?php

namespace App\Controller;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/configuration')]
class ConfigurationController extends AbstractController
{
    /**
     * Liste toutes les configurations.
     */
    #[Route(name: 'app_configuration_index', methods: ['GET'])]
    public function index(ConfigurationRepository $configurationRepository): JsonResponse
    {
        $configurations = $configurationRepository->findAll();

        return $this->json($configurations, JsonResponse::HTTP_OK, [], ['groups' => 'configuration:read']);
    }

    /**
     * Affiche une configuration spécifique par ID.
     */
    #[Route('/{id}', name: 'app_configuration_show', methods: ['GET'])]
    public function show(int $id, ConfigurationRepository $configurationRepository): JsonResponse
    {
        $configuration = $configurationRepository->find($id);

        if (!$configuration) {
            return new JsonResponse(['message' => 'Configuration not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json($configuration, JsonResponse::HTTP_OK, [], ['groups' => 'configuration:read']);
    }

    /**
     * Crée une nouvelle configuration.
     */
    #[Route(name: 'app_configuration_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $configuration = new Configuration();

        // Ajoutez d'autres champs si nécessaire

        $entityManager->persist($configuration);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Configuration created successfully'], JsonResponse::HTTP_CREATED);
    }

    /**
     * Met à jour une configuration existante.
     */
    #[Route('/{id}', name: 'app_configuration_update', methods: ['PUT'])]
    public function update(int $id, Request $request, ConfigurationRepository $configurationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $configuration = $configurationRepository->find($id);

        if (!$configuration) {
            return new JsonResponse(['message' => 'Configuration not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Mettez à jour les champs nécessaires
        // Exemple : $configuration->setNom($data['nom'] ?? $configuration->getNom());

        $entityManager->flush();

        return new JsonResponse(['message' => 'Configuration updated successfully'], JsonResponse::HTTP_OK);
    }

    /**
     * Supprime une configuration.
     */
    #[Route('/{id}', name: 'app_configuration_delete', methods: ['DELETE'])]
    public function delete(int $id, ConfigurationRepository $configurationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $configuration = $configurationRepository->find($id);

        if (!$configuration) {
            return new JsonResponse(['message' => 'Configuration not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($configuration);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Configuration deleted successfully'], JsonResponse::HTTP_NO_CONTENT);
    }
}

