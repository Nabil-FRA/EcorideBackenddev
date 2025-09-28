<?php

namespace App\Controller;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Covoiturage;
use App\Entity\Participe;
use OpenApi\Attributes as OA;

#[Route('/api/mongo')]
#[OA\Tag(name: 'Historique')]
class CovoiturageHistoryController extends AbstractController
{
    /**
     * Insère et récupère les covoiturages terminés dans l'historique MongoDB.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne l'historique complet des covoiturages terminés.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                example: [
                    "_id" => "6515a8b9e4b0b8e8a8d1c1b2",
                    "covoiturage_id" => 101,
                    "participants" => [
                        ["name" => "Dupont Jean", "role" => "passager"],
                        ["name" => "Martin Sophie", "role" => "chauffeur"]
                    ],
                    "departure" => "Paris",
                    "destination" => "Lyon",
                    "date_depart" => "2023-09-28",
                    "date_arrivee" => "2023-09-28",
                    "price" => 25.50,
                    "statut" => "terminé"
                ],
                properties: [
                    new OA\Property(property: '_id', type: 'string'),
                    new OA\Property(property: 'covoiturage_id', type: 'integer'),
                    new OA\Property(property: 'participants', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'role', type: 'string')
                    ])),
                    new OA\Property(property: 'departure', type: 'string'),
                    new OA\Property(property: 'destination', type: 'string'),
                    new OA\Property(property: 'date_depart', type: 'string', format: 'date'),
                    new OA\Property(property: 'date_arrivee', type: 'string', format: 'date'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'statut', type: 'string')
                ]
            )
        )
    )]
    #[Route('/historique_covoiturage', name: 'insert_covoiturage_history', methods: ['GET'])]
    public function insertHistory(EntityManagerInterface $em, MongoDBService $mongoDBService): Response
    {
        $db = $mongoDBService->getDatabase();
        $collection = $db->selectCollection('reservations');

        $covoiturages = $em->getRepository(Covoiturage::class)->findBy(['statut' => 'terminé']);

        foreach ($covoiturages as $covoiturage) {
            $existing = $collection->findOne(['covoiturage_id' => $covoiturage->getId()]);
            if ($existing) {
                continue;
            }

            $participants = $em->getRepository(Participe::class)->findBy(['covoiturage' => $covoiturage]);
            $passengers = [];
            foreach ($participants as $participant) {
                $user = $participant->getUtilisateur();
                $role = $user->isChauffeur() ? 'chauffeur' : ($user->isPassager() ? 'passager' : 'inconnu');
                $passengers[] = [
                    'name' => $user->getNom() . ' ' . $user->getPrenom(),
                    'role' => $role
                ];
            }

            $document = [
                'covoiturage_id' => $covoiturage->getId(),
                'participants' => $passengers,
                'departure' => $covoiturage->getLieuDepart(),
                'destination' => $covoiturage->getLieuArrivee(),
                'date_depart' => $covoiturage->getDateDepart()->format('Y-m-d'),
                'date_arrivee' => $covoiturage->getDateArrivee()->format('Y-m-d'),
                'price' => $covoiturage->getPrixPersonne(),
                'statut' => $covoiturage->getStatut()
            ];

            $collection->insertOne($document);
        }

        $history = $collection->find()->toArray();

        return $this->json($history);
    }
}

