<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version optimisée pour Heroku et MongoDB Atlas
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
        // Ajouter automatiquement les timeouts étendus pour Heroku
        $this->uri = $this->addTimeoutsToUri($uri);
        $this->databaseName = $database;
        $this->logger = $logger;
        
        if ($this->logger) {
            $this->logger->info('MongoDBService initialized', [
                'database' => $database,
                'uri_prefix' => substr($this->uri, 0, 50) . '...',
                'timeouts_added' => true
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
        // Si l'URI contient déjà des paramètres, ajouter &sinon ?
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        
        return $uri . $separator . 'retryWrites=true&w=majority&appName=Cluster0&' .
               'connectTimeoutMS=30000&' .
               'socketTimeoutMS=60000&' .
               'serverSelectionTimeoutMS=30000&' .
               'heartbeatFrequencyMS=10000&' .
               'maxIdleTimeMS=0&' .
               'serverSelectionTimeoutMS=5000';
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
     * Version optimisée pour Heroku avec timeouts étendus
     * 
     * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);
        
        try {
            if ($this->logger) {
                $this->logger->info('Establishing MongoDB connection with extended timeouts', [
                    'uri_prefix' => substr($this->uri, 0, 40) . '...',
                    'database' => $this->databaseName,
                    'connect_timeout' => 30,
                    'socket_timeout' => 60,
                    'server_selection_timeout' => 30
                ]);
            }

            // === CONNEXION AVEC TIMEOUTS ÉTENDUS ===
            // L'URI mongodb+srv:// gère automatiquement TLS/SSL
            $this->client = new Client($this->uri);
            
            // Sélection de la base de données
            $this->database = $this->client->selectDatabase($this->databaseName);
            
            // === TEST DE CONNEXION PROGRESSIF ===
            // Test 1 : Créer une collection de test
            $testCollection = $this->database->selectCollection('__connection_test__');
            
            // Test 2 : Insertion simple (timeout 30s)
            $insertResult = $testCollection->insertOne([
                'test' => 'connection',
                'ping' => 'pong',
                'timestamp' => new \DateTime('now'),
                'connected_from' => 'heroku',
                'connection_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
            ]);
            
            if (!$insertResult->getInsertedId()) {
                throw new \RuntimeException('Failed to insert test document');
            }
            
            // Test 3 : Lecture simple
            $readResult = $testCollection->findOne(['_id' => $insertResult->getInsertedId()]);
            if (!$readResult) {
                throw new \RuntimeException('Failed to read test document');
            }
            
            // Test 4 : Nettoyage
            $testCollection->deleteOne(['_id' => $insertResult->getInsertedId()]);
            
            // === CONNEXION RÉUSSIE ===
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully', [
                    'database' => $this->databaseName,
                    'client_class' => get_class($this->client),
                    'database_class' => get_class($this->database),
                    'connection_time_ms' => $connectionTime,
                    'test_document_id' => $insertResult->getInsertedId()
                ]);
            }
            
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            // Timeout de connexion
            $errorMsg = 'MongoDB connection timeout (30s) - Vérifiez la connectivité réseau Heroku ↔ MongoDB Atlas';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
            // Timeout de sélection de serveur
            $errorMsg = 'MongoDB server selection timeout (30s) - Vérifiez que le cluster MongoDB Atlas est en ligne';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            // Erreur d'authentification
            $errorMsg = 'MongoDB authentication failed - Vérifiez les identifiants dans MONGODB_URI';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            // Erreur d'écriture en bulk (souvent liée à l'auth)
            $errorMsg = 'MongoDB bulk write failed - Vérifiez les privilèges utilisateur MongoDB';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\Exception $e) {
            // Erreur générale
            $errorMsg = 'Unexpected MongoDB error: ' . $e->getMessage();
            $this->logError($errorMsg, $e, $startTime);
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
                'error_type' => get_class($exception ?? new \stdClass())
            ];
            
            if ($exception) {
                $context['exception_message'] = $exception->getMessage();
                $context['exception_code'] = $exception->getCode();
                $context['exception_file'] = $exception->getFile();
                $context['exception_line'] = $exception->getLine();
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
            $this->logger->debug('MongoDB connection closed', [
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
     * Teste la connexion MongoDB (pour debug)
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->isConnected()) {
                $this->getDatabase();
            }
            
            $testCollection = $this->database->selectCollection('__health_check__');
            $result = $testCollection->insertOne(['health_check' => true, 'timestamp' => new \DateTime()]);
            
            if ($result && $result->getInsertedId()) {
                $testCollection->deleteOne(['_id' => $result->getInsertedId()]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('MongoDB health check failed', [
                    'error' => $e->getMessage()
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
        $info = [
            'connected' => $this->isConnected(),
            'database' => $this->getDatabaseName(),
            'uri_prefix' => substr($this->getOriginalUri(), 0, 50) . '...',
            'full_uri_length' => strlen($this->uri),
            'client_class' => $this->client ? get_class($this->client) : null,
            'database_class' => $this->database ? get_class($this->database) : null,
            'health_check' => $this->testConnection()
        ];

        if ($this->isConnected()) {
            try {
                $info['server_info'] = $this->client->selectDatabase('admin')->runCommand(['ismaster' => 1]);
            } catch (\Exception $e) {
                $info['server_info'] = ['error' => $e->getMessage()];
            }
        }

        return $info;
    }

    /**
     * Endpoint de debug pour vérifier la connexion (utiliser dans un contrôleur)
     * 
     * @return array
     */
    public function debugInfo(): array
    {
        return [
            'status' => $this->isConnected() ? 'connected' : 'disconnected',
            'database' => $this->getDatabaseName(),
            'connection_info' => $this->getConnectionInfo(),
            'timestamp' => new \DateTime('now')
        ];
    }
}