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

#[Route('/api/parametre')]
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

    #[Route('/', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $parametres = $this->parametreRepository->findAll();
        $data = $this->serializer->serialize($parametres, 'json', ['groups' => 'parametre:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $parametre = $this->parametreRepository->find($id);

        if (!$parametre) {
            return new JsonResponse(['message' => 'Parametre not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($parametre, 'json', ['groups' => 'parametre:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/', methods: ['POST'])]
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

    #[Route('/{id}', methods: ['PUT'])]
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

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $parametre = $this->parametreRepository->find($id);

        if (!$parametre) {
            return new JsonResponse(['message' => 'Parametre not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($parametre);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Parametre deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
