<?php

namespace App\Service;

use MongoDB\Client;

class MongoDBService
{
    private $client;
    private $database;

    public function __construct(string $uri, string $database)
    {
        $this->client = new Client($uri);
        
        // CORRECTION : Utiliser la variable $database passée en argument
        // au lieu d'un nom codé en dur.
        $this->database = $this->client->selectDatabase($database);
    }

    public function getDatabase()
    {
        return $this->database;
    }
}
