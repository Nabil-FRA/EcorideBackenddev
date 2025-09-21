<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version FINALE pour Heroku + Fixie + MongoDB Atlas
 * SSL simplifié pour éviter les erreurs de handshake
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
        // Ajouter automatiquement les timeouts et paramètres SSL pour Heroku/Fixie
        $this->uri = $this->addHerokuFixieParams($uri);
        $this->databaseName = $database;
        $this->logger = $logger;
        
        if ($this->logger) {
            $this->logger->info('MongoDBService initialized (Heroku+Fixie Final)', [
                'database' => $database,
                'uri_prefix' => substr($this->uri, 0, 50) . '...',
                'ssl_relaxed' => true,
                'fixie_optimized' => true
            ]);
        }
    }

    /**
     * Ajoute tous les paramètres nécessaires pour Heroku + Fixie
     * 
     * @param string $uri URI originale
     * @return string URI optimisée
     */
    private function addHerokuFixieParams(string $uri): string
    {
        $separator = strpos($uri, '?') !== false ? '&' : '?';
        
        // Paramètres essentiels
        $essentialParams = [
            'retryWrites=true',
            'w=majority',
            'appName=Ecoride-Heroku-Fixie-Final'
        ];
        
        // Timeouts étendus
        $timeoutParams = [
            'connectTimeoutMS=90000',      // 90s (Fixie + latence)
            'socketTimeoutMS=120000',      // 2min
            'serverSelectionTimeoutMS=60000' // 60s
        ];
        
        // Paramètres SSL RELAXÉS pour Heroku/Fixie
        $sslParams = [
            'ssl=true',                    // Forcer SSL
            'sslallowinvalidcertificates=true', // Accepter certificats invalides
            'sslallowinvalidhostnames=true',    // Accepter noms d'hôtes invalides
            'sslverifyclientcertificate=false'  // Ne pas vérifier le certificat client
        ];
        
        // Tous les paramètres ensemble
        $allParams = array_merge($essentialParams, $timeoutParams, $sslParams);
        
        return $uri . $separator . implode('&', $allParams);
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
     * VERSION FINALE : SSL relaxé + timeouts max + pas de test complexe
     * 
     * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);
        
        try {
            if ($this->logger) {
                $this->logger->info('Establishing MongoDB connection (Heroku+Fixie Final)', [
                    'uri_prefix' => substr($this->uri, 0, 40) . '...',
                    'database' => $this->databaseName,
                    'ssl_relaxed' => true,
                    'connect_timeout' => 90,
                    'socket_timeout' => 120
                ]);
            }

            // === CONNEXION FINALE ===
            // URI avec SSL relaxé + timeouts max
            // Pas de driverOptions complexes, pas de runCommand
            $this->client = new Client($this->uri);
            
            // Sélection de la base de données
            $this->database = $this->client->selectDatabase($this->databaseName);
            
            // Test ULTRA-MINIMAL : juste vérifier que la DB existe
            $this->minimalConnectionTest();
            
            // === CONNEXION RÉUSSIE ===
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully (FINAL)', [
                    'database' => $this->databaseName,
                    'connection_time_ms' => $connectionTime,
                    'client_class' => get_class($this->client),
                    'database_class' => get_class($this->database),
                    'ssl_relaxed_used' => true,
                    'status' => 'success_final'
                ]);
            }
            
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            $errorMsg = 'MongoDB connection timeout (90s) - Network connectivity issue Heroku ↔ Atlas';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
            $errorMsg = 'MongoDB server selection timeout (60s) - Atlas cluster unreachable';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            $errorMsg = 'MongoDB authentication failed - Check MONGODB_URI credentials';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\Exception $e) {
            $errorMsg = 'Unexpected MongoDB error: ' . $e->getMessage();
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Test de connexion ULTRA-MINIMAL
     * Juste vérifier que la DB est accessible
     * 
     * @return void
     * @throws \RuntimeException Si le test échoue
     */
    private function minimalConnectionTest(): void
    {
        try {
            // Test le plus simple possible : lister les collections
            $collections = $this->database->listCollections();
            $collectionList = iterator_to_array($collections);
            
            if (empty($collectionList)) {
                // Si pas de collections, en créer une vide pour test
                $this->database->createCollection('__minimal_test__');
                $this->database->dropCollection('__minimal_test__');
            }
            
            if ($this->logger) {
                $this->logger->debug('Minimal connection test PASSED', [
                    'collections_count' => count($collectionList),
                    'test_method' => 'listCollections',
                    'status' => 'minimal_ok'
                ]);
            }
            
        } catch (\Exception $e) {
            $errorMsg = 'Minimal connection test failed: ' . $e->getMessage();
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
                'heroku_fixie_final' => true,
                'ssl_relaxed' => true
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
        $originalUri = preg_replace('/&?(connectTimeoutMS|socketTimeoutMS|serverSelectionTimeoutMS|sslallowinvalidcertificates|sslallowinvalidhostnames|sslverifyclientcertificate)=[^&]*/', '', $this->uri);
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
            $this->logger->debug('MongoDB connection closed (Heroku+Fixie Final)', [
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
     * Teste la connexion MongoDB (health check minimal)
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->isConnected()) {
                $this->getDatabase();
            }
            
            // Test minimal : juste lister les collections
            $collections = $this->database->listCollections();
            return $collections->isValid();
            
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('MongoDB health check failed (minimal)', [
                    'error' => $e->getMessage(),
                    'test_method' => 'listCollections'
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
            'heroku_fixie_final' => true,
            'ssl_relaxed' => true,
            'test_method' => 'listCollections',
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
            'status' => $this->isConnected() ? 'connected_final_heroku_fixie' : 'disconnected',
            'database' => $this->getDatabaseName(),
            'connection_info' => $this->getConnectionInfo(),
            'timestamp' => new \DateTime('now'),
            'mode' => 'final_ssl_relaxed_no_runCommand'
        ];
    }
}