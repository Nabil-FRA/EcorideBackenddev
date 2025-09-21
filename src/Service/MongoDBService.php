<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version FINALE pour Heroku + Fixie + MongoDB Atlas
 * Gère la connexion sécurisée TLS et le passage par un proxy HTTP (Fixie)
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
     * @param string $uri Chaîne de connexion MongoDB (mongodb+srv://...)
     * @param string $database Nom de la base de données
     * @param LoggerInterface|null $logger Service de logging (optionnel)
     */
    public function __construct(
        string $uri,
        string $database,
        ?LoggerInterface $logger = null
    ) {
        $this->uri = $this->addHerokuFixieParams($uri);
        $this->databaseName = $database;
        $this->logger = $logger;

        if ($this->logger) {
            $this->logger->info('MongoDBService initialized (Heroku+Fixie Final Corrected)', [
                'database' => $database,
                'uri_prefix' => substr($this->uri, 0, 50) . '...',
                'fixie_optimized' => true
            ]);
        }
    }

    /**
     * Ajoute les paramètres de timeout nécessaires pour Heroku + Fixie
     * @param string $uri URI originale
     * @return string URI optimisée
     */
    private function addHerokuFixieParams(string $uri): string
    {
        $separator = strpos($uri, '?') !== false ? '&' : '?';

        $essentialParams = [
            'retryWrites=true',
            'w=majority',
            'appName=Ecoride-Heroku-Fixie-Final'
        ];

        $timeoutParams = [
            'connectTimeoutMS=90000',
            'socketTimeoutMS=120000',
            'serverSelectionTimeoutMS=60000'
        ];
        
        $allParams = array_merge($essentialParams, $timeoutParams);

        return $uri . $separator . implode('&', $allParams);
    }

    /**
     * Retourne la base de données MongoDB
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
     * Établit la connexion à MongoDB, en utilisant le proxy Fixie si disponible.
     * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);

        try {
            // Options de base du driver, on active TLS
            $driverOptions = [
                'tls' => true,
            ];

            // === CONFIGURATION POUR LE PROXY HTTP FIXIE (`FIXIE_URL`) ===
            $fixieUrl = getenv('FIXIE_URL');

            if ($fixieUrl) {
                $this->logger?->info('Proxy HTTP Fixie détecté, configuration en cours...', ['url_prefix' => substr($fixieUrl, 0, 20)]);
                
                $proxyInfo = parse_url($fixieUrl);

                if ($proxyInfo) {
                    // Pour un proxy HTTP, on doit créer un "contexte de flux" PHP
                    $auth = base64_encode("{$proxyInfo['user']}:{$proxyInfo['pass']}");
                    
                    $contextOptions = [
                        'http' => [
                            'proxy' => "tcp://{$proxyInfo['host']}:{$proxyInfo['port']}",
                            'request_fulluri' => true,
                            'header' => "Proxy-Authorization: Basic $auth",
                        ],
                        // Contexte SSL vide pour que le driver MongoDB gère le TLS via le tunnel
                        'ssl' => [] 
                    ];

                    $streamContext = stream_context_create($contextOptions);
                    
                    // On ajoute le contexte complet aux options du driver
                    $driverOptions['context'] = $streamContext;
                }
            }

            // Instanciation du client avec toutes les options (TLS + Contexte HTTP si présent)
            $this->client = new Client($this->uri, [], $driverOptions);
            
            $this->database = $this->client->selectDatabase($this->databaseName);
            $this->minimalConnectionTest();

            // === CONNEXION RÉUSSIE ===
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully', [
                    'database' => $this->databaseName,
                    'via_proxy' => !empty($fixieUrl),
                    'connection_time_ms' => $connectionTime,
                ]);
            }

        } catch (\Exception $e) {
            $errorMsg = 'Unexpected MongoDB error: ' . $e->getMessage();
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Test de connexion minimal
     * @throws \RuntimeException Si le test échoue
     */
    private function minimalConnectionTest(): void
    {
        try {
            $this->database->listCollectionNames();
            $this->logger?->debug('Minimal connection test PASSED');
        } catch (\Exception $e) {
            $errorMsg = 'Minimal connection test failed: ' . $e->getMessage();
            $this->logger?->error($errorMsg, ['exception' => $e->getMessage()]);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Journalise une erreur MongoDB
     */
    private function logError(string $message, \Exception $exception, float $startTime): void
    {
        if ($this->logger) {
            $context = [
                'uri_prefix' => substr($this->uri, 0, 30) . '...',
                'database' => $this->databaseName,
                'error_type' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'connection_attempt_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
            ];
            $this->logger->error($message, $context);
        }
    }

    // --- AUTRES MÉTHODES ---

    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->connect();
        }
        return $this->client;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function close(): void
    {
        $this->client = null;
        $this->database = null;
        $this->logger?->debug('MongoDB connection closed');
    }

    public function isConnected(): bool
    {
        return $this->client !== null && $this->database !== null;
    }
}