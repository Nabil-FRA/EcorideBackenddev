<?php

namespace App\Controller;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use MongoDB\Client as MongoDBClient;


class MongoController extends AbstractController
{
    #[Route('/mongo/test', name: 'mongo_test')]
    public function index(MongoDBService $mongoDBService): Response
    {
        $db = $mongoDBService->getDatabase();
        
        // Créer une collection 'test' si elle n'existe pas
        $collection = $db->selectCollection('test');
        
        // Insérer un document dans la collection
        $collection->insertOne(['message' => 'Hello MongoDB!', 'created_at' => new \DateTime()]);

        // Lire les documents de la collection
        $documents = $collection->find()->toArray();

        // Retourner les documents en JSON
        return $this->json($documents);
    }
    
    #[Route('/mongo/confirmation_covoiturage', name: 'confirmation_covoiturage', methods: ['GET'])]
    public function historiqueCovoiturage(): Response
    {
        $client = new MongoDBClient("mongodb://localhost:27017");
        $collection = $client->EcoRide->participations;

        $historique = $collection->find()->toArray();

        return $this->json($historique);
    }
}


