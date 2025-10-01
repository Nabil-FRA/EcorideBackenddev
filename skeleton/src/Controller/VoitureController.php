<?php

namespace App\Controller;

use App\Repository\GereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Gere;



class VoitureController extends AbstractController
{
    #[Route('/api/voitures/utilisateur', name: 'get_voitures_utilisateur', methods: ['GET'])]
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
