<?php

namespace App\Controller;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request; // AJOUT : Nécessaire pour la pagination
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
        // CORRECTION : Remplacé par une opération en lecture seule pour un test plus propre.
        try {
            $db = $mongoDBService->getDatabase();
            $collections = $db->listCollections();
            $collectionNames = [];
            foreach ($collections as $collection) {
                $collectionNames[] = $collection->getName();
            }
            return $this->json(['status' => 'success', 'collections' => $collectionNames]);
        } catch (MongoDBException $e) {
            return $this->json(['error' => 'MongoDB Connection Error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les participations confirmées avec pagination.
     */
    #[OA\Parameter(name: "page", in: "query", description: "Numéro de la page à récupérer", required: false, schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: "limit", in: "query", description: "Nombre d'éléments par page", required: false, schema: new OA\Schema(type: 'integer', default: 50))]
    #[OA\Response(
        response: 200,
        description: "Retourne la liste brute des participations enregistrées.",
        // ... (Le reste de la documentation OA)
    )]
    #[OA\Response(response: 500, description: "Impossible de récupérer les confirmations.")]
    #[Route('/confirmation_covoiturage', name: 'confirmation_covoiturage', methods: ['GET'])]
    public function confirmationCovoiturage(MongoDBService $mongoDBService, Request $request): Response
    {
        // CORRECTION : Ajout de la pagination pour éviter les surcharges de mémoire.
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 50);
            $skip = ($page - 1) * $limit;

            $db = $mongoDBService->getDatabase();
            $collection = $db->selectCollection('participations');
            
            $options = [
                'limit' => $limit,
                'skip' => $skip,
                'sort' => ['dateParticipation' => -1] // Trie par date, du plus récent au plus ancien
            ];

            $confirmations = $collection->find([], $options)->toArray();
            
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
        // ... (Le reste de la documentation OA)
    )]
    #[OA\Response(response: 500, description: "Impossible de récupérer l'historique.")]
    #[Route('/historique_covoiturage', name: 'historique_covoiturage', methods: ['GET'])]
    public function historiqueCovoiturage(MongoDBService $mongoDBService): Response
    {
        try {
            $db = $mongoDBService->getDatabase();
            $collection = $db->selectCollection('participations');

            // CORRECTION : Bug de la date d'arrivée et prix en dur corrigés.
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
                        'date_arrivee' => ['$first' => '$covoiturage.dateArrivee'], // Corrigé ici
                        'price' => ['$first' => '$covoiturage.prixPersonne'],      // Corrigé ici
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