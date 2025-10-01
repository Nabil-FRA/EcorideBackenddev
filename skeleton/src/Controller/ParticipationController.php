<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Participe;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use MongoDB\Client as MongoDBClient;


class ParticipationController extends AbstractController
{   #[Route('/api/covoiturage/{id}/participer', name: 'covoiturage_participer', methods: ['POST'])]
    public function participer(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return $this->json(['message' => 'Veuillez vous connecter pour participer'], Response::HTTP_UNAUTHORIZED);
        }

        $utilisateur = $entityManager->getRepository(Utilisateur::class)->find($utilisateur->getId());

        if (!$utilisateur->isPassager()) {
            return $this->json(['message' => 'Seuls les passagers peuvent participer'], Response::HTTP_FORBIDDEN);
        }

        $covoiturage = $entityManager->getRepository(Covoiturage::class)->find($id);
        if (!$covoiturage) {
            return $this->json(['message' => 'Covoiturage introuvable'], Response::HTTP_NOT_FOUND);
        }

        if ($covoiturage->getNbPlace() <= 0) {
            return $this->json(['message' => 'Plus de places disponibles'], Response::HTTP_BAD_REQUEST);
        }

        if ($utilisateur->getCredits() < 2) {
            return $this->json(['message' => 'Vous devez avoir au moins 2 crédits pour participer'], Response::HTTP_PAYMENT_REQUIRED);
        }

        if (!$request->query->get('confirm')) {
            return $this->json(['message' => 'Veuillez confirmer votre participation'], Response::HTTP_ACCEPTED);
        }

        $nouveauSolde = $utilisateur->getCredits() - 2;
        $utilisateur->setCredits($nouveauSolde);
        $covoiturage->setNbPlace($covoiturage->getNbPlace() - 1);

        $participation = new Participe();
        $participation->setUtilisateur($utilisateur);
        $participation->setCovoiturage($covoiturage);
        $entityManager->persist($participation);
        $entityManager->flush();

        // ---------------------------
        // Enregistrement dans MongoDB
        // ---------------------------
        $client = new MongoDBClient("mongodb://localhost:27017");
        $collection = $client->EcoRide->participations;

        $participationData = [
            'utilisateur' => [
                'id' => $utilisateur->getId(),
                'nom' => $utilisateur->getNom(),
                'prenom' => $utilisateur->getPrenom(),
                'email' => $utilisateur->getEmail(),
            ],
            'covoiturage' => [
                'id' => $covoiturage->getId(),
                'lieuDepart' => $covoiturage->getLieuDepart(),
                'lieuArrivee' => $covoiturage->getLieuArrivee(),
                'dateDepart' => $covoiturage->getDateDepart()->format('Y-m-d'),
                'heureDepart' => $covoiturage->getHeureDepart()->format('H:i:s'),
            ],
            'creditsUtilises' => 2,
            'dateParticipation' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        $collection->insertOne($participationData);

        return $this->json([
            'message' => 'Participation confirmée et enregistrée dans MongoDB',
            'creditsRestants' => $utilisateur->getCredits()
        ], Response::HTTP_OK);
    }
}
