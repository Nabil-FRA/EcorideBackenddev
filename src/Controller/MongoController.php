<?php

namespace App\Controller;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use OpenApi\Attributes as OA;

#[Route('/api/mongo')]
#[OA\Tag(name: 'MongoDB')]
class MongoController extends AbstractController
{
    /**
     * Route de test pour vérifier la connexion à MongoDB.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne les documents de la collection de test.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: '_id', type: 'object'),
                new OA\Property(property: 'message', type: 'string', example: 'Hello MongoDB!'),
                new OA\Property(property: 'created_at', type: 'object')
            ])
        )
    )]
    #[OA\Response(response: 500, description: "Erreur de connexion à MongoDB.")]
    #[Route('/test', name: 'mongo_test', methods: ['GET'])]
    public function index(MongoDBService $mongoDBService): Response
    {
        try {
            $db = $mongoDBService->getDatabase();
            $collection = $db->selectCollection('test');
            $collection->insertOne(['message' => 'Hello MongoDB!', 'created_at' => new \DateTime()]);
            $documents = $collection->find()->toArray();
            return $this->json($documents);
        } catch (MongoDBException $e) {
            return $this->json(['error' => 'MongoDB Connection Error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les participations confirmées.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste brute des participations enregistrées.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: '_id', type: 'object'),
                new OA\Property(property: 'utilisateur', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'nom', type: 'string'),
                ]),
                new OA\Property(property: 'covoiturage', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'lieuDepart', type: 'string'),
                ]),
                new OA\Property(property: 'dateParticipation', type: 'string')
            ])
        )
    )]
    #[OA\Response(response: 500, description: "Impossible de récupérer les confirmations.")]
    #[Route('/confirmation_covoiturage', name: 'confirmation_covoiturage', methods: ['GET'])]
    public function confirmationCovoiturage(MongoDBService $mongoDBService): Response
    {
        try {
            $db = $mongoDBService->getDatabase();
            $collection = $db->selectCollection('participations');
            $confirmations = $collection->find()->toArray();
            return $this->json($confirmations);
        } catch (MongoDBException $e) {
            return $this->json(['error' => 'Could not fetch confirmations: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère l'historique des covoiturages en regroupant les participants.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne l'historique des covoiturages avec les participants regroupés.",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(properties: [
                new OA\Property(property: 'covoiturage_id', type: 'integer'),
                new OA\Property(property: 'participants', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'role', type: 'string')
                ])),
                new OA\Property(property: 'departure', type: 'string'),
                new OA\Property(property: 'destination', type: 'string'),
                new OA\Property(property: 'date_depart', type: 'string'),
                new OA\Property(property: 'statut', type: 'string', example: 'Confirmé')
            ])
        )
    )]
    #[OA\Response(response: 500, description: "Impossible de récupérer l'historique.")]
    #[Route('/historique_covoiturage', name: 'historique_covoiturage', methods: ['GET'])]
    public function historiqueCovoiturage(MongoDBService $mongoDBService): Response
    {
        try {
            $db = $mongoDBService->getDatabase();
            $collection = $db->selectCollection('participations');

            $pipeline = [
                [
                    '$group' => [
                        '_id' => '$covoiturage.id',
                        'participants' => [
                            '$push' => [
                                'name' => '$utilisateur.nom',
                                'role' => 'Passager'
                            ]
                        ],
                        'departure' => ['$first' => '$covoiturage.lieuDepart'],
                        'destination' => ['$first' => '$covoiturage.lieuArrivee'],
                        'date_depart' => ['$first' => '$covoiturage.dateDepart'],
                        'date_arrivee' => ['$first' => '$covoiturage.dateDepart'],
                        'price' => ['$first' => 2],
                        'statut' => ['$first' => 'Confirmé'],
                    ]
                ],
                [
                    '$project' => [
                        '_id' => 0,
                        'covoiturage_id' => '$_id',
                        'participants' => '$participants',
                        'departure' => '$departure',
                        'destination' => '$destination',
                        'date_depart' => '$date_depart',
                        'date_arrivee' => '$date_arrivee',
                        'price' => '$price',
                        'statut' => '$statut',
                    ]
                ]
            ];

            $historique = $collection->aggregate($pipeline)->toArray();

            return $this->json($historique);
        } catch (MongoDBException $e) {
            return $this->json(['error' => 'Could not fetch history: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
