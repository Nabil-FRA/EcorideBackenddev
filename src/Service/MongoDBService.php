<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version ULTRA-SIMPLIFIÉE pour Heroku + Fixie + MongoDB Atlas
 * Compatible avec TOUTES les versions d'extension MongoDB
 * 
 * @author Nabil
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
        // Ajouter automatiquement les timeouts étendus pour Heroku + Fixie
        $this->uri = $this->addTimeoutsToUri($uri);
        $this->databaseName = $database;
        $this->logger = $logger;
        
        if ($this->logger) {
            $this->logger->info('MongoDBService initialized (Ultra-Simple)', [
                'database' => $database,
                'uri_prefix' => substr($this->uri, 0, 50) . '...',
                'timeouts_added' => true,
                'no_runCommand' => true  // Mode ultra-simple sans runCommand
            ]);
        }
    }

    /**
     * Ajoute les timeouts étendus à l'URI MongoDB
     * 
     * @param string $uri URI originale
     * @return string URI avec timeouts
     */
    private function addTimeoutsToUri(string $uri): string
    {
        // Si l'URI contient déjà des paramètres, ajouter & sinon ?
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        
        // Paramètres ULTRA-SIMPLIFIÉS (seulement les essentiels)
        $params = [
            'retryWrites=true',
            'w=majority',
            'appName=Ecoride-Heroku-Fixie'
        ];
        
        // Timeouts essentiels (déjà inclus dans driverOptions)
        $timeoutParams = [
            'connectTimeoutMS=60000',      // 60s
            'socketTimeoutMS=90000',       // 90s
            'serverSelectionTimeoutMS=45000' // 45s
        ];
        
        return $uri . $separator . implode('&', array_merge($params, $timeoutParams));
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
     * VERSION ULTRA-SIMPLIFIÉE : AUCUN runCommand(), seulement insert/find/delete
     * 
     * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);
        
        try {
            if ($this->logger) {
                $this->logger->info('Establishing MongoDB connection (Ultra-Simple mode)', [
                    'uri_prefix' => substr($this->uri, 0, 40) . '...',
                    'database' => $this->databaseName,
                    'connect_timeout' => 60,
                    'no_runCommand' => true,
                    'test_method' => 'insert_find_delete'
                ]);
            }

            // === CONNEXION ULTRA-SIMPLE ===
            // Pas de proxy complexe, pas de runCommand, juste Client de base
            $this->client = new Client($this->uri);
            
            // Sélection de la base de données
            $this->database = $this->client->selectDatabase($this->databaseName);
            
            // === TEST ULTRA-SIMPLE (SEULEMENT insert/find/delete) ===
            $this->ultraSimpleConnectionTest();
            
            // === CONNEXION RÉUSSIE ===
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully (Ultra-Simple)', [
                    'database' => $this->databaseName,
                    'connection_time_ms' => $connectionTime,
                    'client_class' => get_class($this->client),
                    'database_class' => get_class($this->database),
                    'test_method_used' => 'insert_find_delete'
                ]);
            }
            
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            $errorMsg = 'MongoDB connection timeout (60s) - Vérifiez la connectivité réseau Heroku ↔ MongoDB Atlas';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
            $errorMsg = 'MongoDB server selection timeout (45s) - Vérifiez que le cluster MongoDB Atlas est en ligne';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            $errorMsg = 'MongoDB authentication failed - Vérifiez les identifiants dans MONGODB_URI';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\Exception $e) {
            $errorMsg = 'Unexpected MongoDB error: ' . $e->getMessage();
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Test de connexion ULTRA-SIMPLE (AUCUN runCommand)
     * Utilise seulement insertOne + findOne + deleteOne
     * 
     * @return void
     * @throws \RuntimeException Si le test échoue
     */
    private function ultraSimpleConnectionTest(): void
    {
        try {
            // Créer une collection de test
            $testCollection = $this->database->selectCollection('__ultra_simple_test__');
            
            // Test 1 : Insertion simple
            $testDoc = [
                'ultra_simple_test' => true,
                'timestamp' => new \DateTime('now'),
                'method' => 'insert_find_delete',
                'connected_from' => 'heroku_simple'
            ];
            
            $insertResult = $testCollection->insertOne($testDoc);
            
            if (!$insertResult || !$insertResult->getInsertedId()) {
                throw new \RuntimeException('Failed to insert test document (ultra-simple mode)');
            }
            
            $insertedId = $insertResult->getInsertedId();
            
            // Test 2 : Lecture simple
            $readResult = $testCollection->findOne(['_id' => $insertedId]);
            
            if (!$readResult || $readResult['ultra_simple_test'] !== true) {
                throw new \RuntimeException('Failed to read test document (ultra-simple mode)');
            }
            
            // Test 3 : Suppression simple
            $deleteResult = $testCollection->deleteOne(['_id' => $insertedId]);
            
            if ($deleteResult->getDeletedCount() !== 1) {
                throw new \RuntimeException('Failed to delete test document (ultra-simple mode)');
            }
            
            if ($this->logger) {
                $this->logger->debug('Ultra-simple connection test PASSED', [
                    'insert_id' => $insertedId,
                    'read_success' => true,
                    'delete_count' => $deleteResult->getDeletedCount(),
                    'test_method' => 'insert_find_delete'
                ]);
            }
            
        } catch (\Exception $e) {
            $errorMsg = 'Ultra-simple connection test failed: ' . $e->getMessage();
            if ($this->logger) {
                $this->logger->error($errorMsg, ['exception' => $e->getMessage()]);
            }
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Journalise une erreur MongoDB avec métriques de timing
     * 
     * @param string $message Message d'erreur
     * @param \Exception|null $exception Exception associée
     * @param float $startTime Timestamp de début de connexion
     * @return void
     */
    private function logError(string $message, ?\Exception $exception = null, float $startTime = 0): void
    {
        if ($this->logger) {
            $context = [
                'uri_prefix' => substr($this->uri, 0, 30) . '...',
                'database' => $this->databaseName,
                'error_type' => get_class($exception ?? new \stdClass()),
                'ultra_simple_mode' => true
            ];
            
            if ($exception) {
                $context['exception_message'] = $exception->getMessage();
                $context['exception_code'] = $exception->getCode();
            }
            
            if ($startTime > 0) {
                $context['connection_attempt_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';
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
     * Retourne l'URI de connexion MongoDB complète (avec timeouts)
     * 
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Retourne l'URI de connexion MongoDB originale (sans timeouts)
     * 
     * @return string
     */
    public function getOriginalUri(): string
    {
        // Enlever les paramètres ajoutés
        $originalUri = preg_replace('/&?(connectTimeoutMS|socketTimeoutMS|serverSelectionTimeoutMS|heartbeatFrequencyMS|maxIdleTimeMS)=[^&]*/', '', $this->uri);
        return preg_replace('/\?$/', '', $originalUri);
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
            $this->logger->debug('MongoDB connection closed (ultra-simple mode)', [
                'database' => $this->databaseName
            ]);
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
     * Teste la connexion MongoDB (health check ULTRA-SIMPLE)
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->isConnected()) {
                $this->getDatabase();
            }
            
            // Test ULTRA-SIMPLE : juste une insertion/lecture
            $testCollection = $this->database->selectCollection('__health_check__');
            $result = $testCollection->insertOne(['health_check' => true]);
            
            if ($result && $result->getInsertedId()) {
                $testCollection->deleteOne(['_id' => $result->getInsertedId()]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('MongoDB health check failed (ultra-simple)', [
                    'error' => $e->getMessage(),
                    'test_method' => 'insert_delete'
                ]);
            }
            return false;
        }
    }

    /**
     * Méthode magique pour compatibilité avec l'ancien code
     * 
     * @return \MongoDB\Database
     */
    public function __invoke(): \MongoDB\Database
    {
        return $this->getDatabase();
    }

    /**
     * Méthode de débogage - retourne les infos de connexion complètes
     * 
     * @return array
     */
    public function getConnectionInfo(): array
    {
        return [
            'connected' => $this->isConnected(),
            'database' => $this->getDatabaseName(),
            'uri_prefix' => substr($this->getOriginalUri(), 0, 50) . '...',
            'ultra_simple_mode' => true,
            'test_method' => 'insert_find_delete',
            'client_class' => $this->client ? get_class($this->client) : null,
            'database_class' => $this->database ? get_class($this->database) : null,
            'health_check' => $this->testConnection()
        ];
    }

    /**
     * Endpoint de debug pour vérifier la connexion (utiliser dans un contrôleur)
     * 
     * @return array
     */
    public function debugInfo(): array
    {
        return [
            'status' => $this->isConnected() ? 'connected_ultra_simple' : 'disconnected',
            'database' => $this->getDatabaseName(),
            'connection_info' => $this->getConnectionInfo(),
            'timestamp' => new \DateTime('now'),
            'mode' => 'ultra_simple_no_runCommand'
        ];
    }
}