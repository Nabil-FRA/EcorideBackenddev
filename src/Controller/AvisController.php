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

#[Route('/api/avis')]
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
    #[Route('/{id}', name: 'app_avis_show', methods: ['GET'])]
    public function show(Avis $avis): JsonResponse
    {
        $response = $this->serializer->serialize($avis, 'json', ['groups' => 'avis:read']);

        return new JsonResponse($response, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Créer un nouvel avis.
     */
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
    #[Route('/{id}', name: 'app_avis_delete', methods: ['DELETE'])]
    public function delete(Avis $avis): JsonResponse
    {
        $this->entityManager->remove($avis);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Avis deleted successfully'], JsonResponse::HTTP_NO_CONTENT);
    }
}

