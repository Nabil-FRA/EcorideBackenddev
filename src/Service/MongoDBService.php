<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version simplifiée - Compatible MongoDB Atlas et Heroku
 */
class MongoDBService
{
    private ?Client $client = null;
    private ?\MongoDB\Database $database = null;
    private string $uri;
    private string $databaseName;
    private ?LoggerInterface $logger = null;

    /**
     * Constructeur du service MongoDB
     * 
     * @param string $uri Chaîne de connexion MongoDB (mongodb+srv://...)
     * @param string $database Nom de la base de données
     * @param LoggerInterface|null $logger Service de logging (optionnel)
     */
    public function __construct(
        string $uri, 
        string $database, 
        ?LoggerInterface $logger = null
    ) {
        $this->uri = $uri;
        $this->databaseName = $database;
        $this->logger = $logger;
        
        if ($this->logger) {
            $this->logger->info('MongoDBService initialized', [
                'database' => $database,
                'uri_prefix' => substr($uri, 0, 50) . '...'
            ]);
        }
    }

    /**
     * Retourne la base de données MongoDB
     * Crée la connexion si elle n'existe pas
     * 
     * @return \MongoDB\Database
     * @throws \RuntimeException Si la connexion échoue
     */
    public function getDatabase(): \MongoDB\Database
    {
        if ($this->database === null) {
            $this->connect();
        }
        
        return $this->database;
    }

    /**
     * Établit la connexion à MongoDB
     * Version ULTRA-SIMPLIFIÉE - sans test complexe
     * 
     * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        try {
            if ($this->logger) {
                $this->logger->info('Establishing MongoDB connection', [
                    'uri_prefix' => substr($this->uri, 0, 30) . '...',
                    'database' => $this->databaseName
                ]);
            }

            // === CONNEXION SIMPLE ===
            // L'URI mongodb+srv:// gère automatiquement TLS/SSL
            // Pas d'options complexes - juste la connexion de base
            $this->client = new Client($this->uri);
            
            // Sélection de la base de données
            $this->database = $this->client->selectDatabase($this->databaseName);
            
            // === TEST ULTRA-SIMPLE ===
            // Insertion d'un document test pour vérifier la connexion
            $testCollection = $this->database->selectCollection('__test_connection__');
            $testCollection->insertOne([
                'ping' => 'pong',
                'timestamp' => new \DateTime(),
                'connected' => true
            ]);
            
            // Supprimer le document test
            $testCollection->deleteOne(['ping' => 'pong']);
            
            // === CONNEXION RÉUSSIE ===
            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully', [
                    'database' => $this->databaseName,
                    'client' => get_class($this->client),
                    'database_class' => get_class($this->database)
                ]);
            }
            
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            // Erreur de timeout
            $errorMsg = 'MongoDB connection timeout - Vérifiez votre connexion réseau et MongoDB Atlas';
            $this->logError($errorMsg, $e);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\ConnectionException $e) {
            // Erreur de connexion générale
            $errorMsg = 'MongoDB connection failed - Vérifiez vos identifiants et l\'accès réseau';
            $this->logError($errorMsg, $e);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            // Erreur d'authentification
            $errorMsg = 'MongoDB authentication failed - Vérifiez votre nom d\'utilisateur et mot de passe';
            $this->logError($errorMsg, $e);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\Exception $e) {
            // Erreur générale
            $errorMsg = 'Unexpected MongoDB error: ' . $e->getMessage();
            $this->logError($errorMsg, $e);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Journalise une erreur MongoDB
     * 
     * @param string $message Message d'erreur
     * @param \Exception|null $exception Exception associée
     */
    private function logError(string $message, ?\Exception $exception = null): void
    {
        if ($this->logger) {
            $context = [
                'uri_prefix' => substr($this->uri, 0, 30) . '...',
                'database' => $this->databaseName,
                'error_type' => get_class($exception ?? new \stdClass())
            ];
            
            if ($exception) {
                $context['exception_message'] = $exception->getMessage();
                $context['exception_code'] = $exception->getCode();
                $context['trace'] = $exception->getTraceAsString();
            }
            
            $this->logger->error($message, $context);
        }
    }

    /**
     * Retourne le client MongoDB brut
     * 
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->connect();
        }
        
        return $this->client;
    }

    /**
     * Retourne l'URI de connexion MongoDB
     * 
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Retourne le nom de la base de données
     * 
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Ferme la connexion MongoDB
     * 
     * @return void
     */
    public function close(): void
    {
        $this->client = null;
        $this->database = null;
        
        if ($this->logger) {
            $this->logger->debug('MongoDB connection closed');
        }
    }

    /**
     * Vérifie si la connexion MongoDB est active
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client !== null && $this->database !== null;
    }

    /**
     * Méthode magique pour compatibilité avec l'ancien code
     * Permet d'utiliser le service comme une fonction
     * 
     * @return \MongoDB\Database
     */
    public function __invoke(): \MongoDB\Database
    {
        return $this->getDatabase();
    }

    /**
     * Méthode de débogage - retourne les infos de connexion
     * 
     * @return array
     */
    public function getConnectionInfo(): array
    {
        return [
            'connected' => $this->isConnected(),
            'uri' => $this->getUri(),
            'database' => $this->getDatabaseName(),
            'client_class' => $this->client ? get_class($this->client) : null,
            'database_class' => $this->database ? get_class($this->database) : null
        ];
    }
}