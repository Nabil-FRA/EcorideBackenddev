<?php

namespace App\Controller;

use App\Entity\Marque;
use App\Repository\MarqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
// Import important pour la désérialisation
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/marque')]
class MarqueController extends AbstractController
{
    /**
     * Liste toutes les marques.
     */
    #[Route(name: 'app_marque_index', methods: ['GET'])]
    public function index(MarqueRepository $marqueRepository): JsonResponse
    {
        $marques = $marqueRepository->findAll();

        // On sérialise avec le groupe 'marque:read'
        return $this->json(
            $marques,
            JsonResponse::HTTP_OK,
            [],
            ['groups' => 'marque:read']
        );
    }

    /**
     * Affiche une marque spécifique par ID.
     */
    #[Route('/{id}', name: 'app_marque_show', methods: ['GET'])]
    public function show(int $id, MarqueRepository $marqueRepository): JsonResponse
    {
        $marque = $marqueRepository->find($id);

        if (!$marque) {
            return new JsonResponse(['message' => 'Marque not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Sérialise la Marque avec le groupe 'marque:read'
        return $this->json(
            $marque,
            JsonResponse::HTTP_OK,
            [],
            ['groups' => 'marque:read']
        );
    }

    /**
     * Crée une nouvelle marque (POST) en désérialisant le JSON directement dans l'entité.
     */
    #[Route(name: 'app_marque_create', methods: ['POST'])]
    public function create(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // On désérialise le JSON → Objet Marque
            // 'marque:write' nous permet de peupler les champs annotés avec #[Groups(['marque:write'])]
            /** @var Marque $marque */
            $marque = $serializer->deserialize(
                $request->getContent(),
                Marque::class,
                'json',
                ['groups' => ['marque:write']]
            );

            // On persiste en base
            $entityManager->persist($marque);
            $entityManager->flush();

            // On renvoie la Marque créée, sérialisée avec 'marque:read'
            return $this->json(
                $marque,
                JsonResponse::HTTP_CREATED,
                [],
                ['groups' => 'marque:read']
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['message' => 'Error: ' . $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Met à jour une marque existante (PUT).
     */
    #[Route('/{id}', name: 'app_marque_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        SerializerInterface $serializer,
        MarqueRepository $marqueRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // On récupère la marque existante
        $marque = $marqueRepository->find($id);

        if (!$marque) {
            return new JsonResponse(['message' => 'Marque not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            // On désérialise EN mettant à jour l'objet existant (grâce à 'object_to_populate')
            $serializer->deserialize(
                $request->getContent(),
                Marque::class,
                'json',
                [
                    'groups' => ['marque:write'],
                    'object_to_populate' => $marque // On applique les nouvelles valeurs dans l'objet existant
                ]
            );

            // On flush pour enregistrer les modifications
            $entityManager->flush();

            // Renvoie la marque mise à jour
            return $this->json(
                $marque,
                JsonResponse::HTTP_OK,
                [],
                ['groups' => 'marque:read']
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['message' => 'Error: ' . $e->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Supprime une marque (DELETE).
     */
    #[Route('/{id}', name: 'app_marque_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        MarqueRepository $marqueRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $marque = $marqueRepository->find($id);

        if (!$marque) {
            return new JsonResponse(['message' => 'Marque not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $entityManager->remove($marque);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Marque deleted successfully'], JsonResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Error: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
