<?php

namespace App\Controller;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use MongoDB\Driver\Exception\Exception as MongoDBException;

class MongoController extends AbstractController
{
    /**
     * Route de test pour vérifier la connexion à MongoDB.
     */
    #[Route('/mongo/test', name: 'mongo_test')]
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
     * La structure de données simple correspond au deuxième fetch du front-end.
     */
    #[Route('/mongo/confirmation_covoiturage', name: 'confirmation_covoiturage', methods: ['GET'])]
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
     * Cette logique correspond maintenant au premier fetch du front-end.
     */
    #[Route('/mongo/historique_covoiturage', name: 'historique_covoiturage', methods: ['GET'])]
    public function historiqueCovoiturage(MongoDBService $mongoDBService): Response
    {
        try {
            $db = $mongoDBService->getDatabase();
            $collection = $db->selectCollection('participations');

            // Pipeline d'agrégation pour regrouper les participants par covoiturage
            $pipeline = [
                [
                    '$group' => [
                        '_id' => '$covoiturage.id',
                        'participants' => [
                            '$push' => [
                                'name' => '$utilisateur.nom',
                                'role' => 'Passager' // Vous pouvez ajuster ce rôle si nécessaire
                            ]
                        ],
                        'departure' => ['$first' => '$covoiturage.lieuDepart'],
                        'destination' => ['$first' => '$covoiturage.lieuArrivee'],
                        'date_depart' => ['$first' => '$covoiturage.dateDepart'],
                        // NOTE: Les champs suivants ne sont pas dans vos données MongoDB
                        // J'ajoute des valeurs par défaut pour éviter que le front-end ne plante.
                        'date_arrivee' => ['$first' => '$covoiturage.dateDepart'], // Placeholder
                        'price' => ['$first' => 2], // Placeholder, car chaque participation coûte 2 crédits
                        'statut' => ['$first' => 'Confirmé'], // Placeholder
                    ]
                ],
                [
                    // Projeter les champs pour qu'ils correspondent exactement au front-end
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

