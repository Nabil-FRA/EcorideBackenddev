<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version FINALE pour Heroku + Fixie + MongoDB Atlas
 * Gère la connexion sécurisée TLS 1.2 à travers un proxy HTTP (Fixie)
 * @author Nabil
 */
class MongoDBService
{
    private ?Client $client = null;
    private ?\MongoDB\Database $database = null;
    private string $uri;
    private string $databaseName;
    private ?LoggerInterface $logger = null;

    public function __construct(
        string $uri,
        string $database,
        ?LoggerInterface $logger = null
    ) {
        $this->uri = $this->addHerokuFixieParams($uri);
        $this->databaseName = $database;
        $this->logger = $logger;

        if ($this->logger) {
            $this->logger->info('MongoDBService initialized (Heroku+Fixie Final)', [
                'database' => $database,
                'uri_prefix' => substr($this->uri, 0, 50) . '...',
            ]);
        }
    }

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

    public function getDatabase(): \MongoDB\Database
    {
        if ($this->database === null) {
            $this->connect();
        }
        return $this->database;
    }

    /**
     * Établit la connexion à MongoDB, en utilisant le proxy Fixie et le TLS 1.2 si disponible.
     * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);

        try {
            $driverOptions = [
                'tls' => true,
            ];

            // Configuration pour le proxy HTTP Fixie (`FIXIE_URL`)
            $fixieUrl = getenv('FIXIE_URL');

            if ($fixieUrl) {
                $this->logger?->info('Proxy HTTP Fixie détecté, configuration du contexte de flux...');
                
                $proxyInfo = parse_url($fixieUrl);

                if ($proxyInfo) {
                    $auth = base64_encode("{$proxyInfo['user']}:{$proxyInfo['pass']}");
                    
                    // On crée un contexte qui définit À LA FOIS le proxy et les options TLS
                    $contextOptions = [
                        'http' => [
                            'proxy' => "tcp://{$proxyInfo['host']}:{$proxyInfo['port']}",
                            'request_fulluri' => true,
                            'header' => "Proxy-Authorization: Basic $auth",
                        ],
                        // ✅ CORRECTION : On ajoute explicitement les options TLS 1.2 ici
                        'ssl' => [
                            'verify_peer' => true,
                            'verify_peer_name' => true,
                            // Forcer l'utilisation de TLS 1.2
                            'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                        ]
                    ];

                    $streamContext = stream_context_create($contextOptions);
                    $driverOptions['context'] = $streamContext;
                }
            }

            // Instanciation du client avec toutes les options
            $this->client = new Client($this->uri, [], $driverOptions);
            
            $this->database = $this->client->selectDatabase($this->databaseName);
            $this->minimalConnectionTest();

            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger?->info('MongoDB connection established successfully', [
                'database' => $this->databaseName,
                'via_proxy' => !empty($fixieUrl),
                'connection_time_ms' => $connectionTime,
            ]);

        } catch (\Exception $e) {
            $errorMsg = 'Unexpected MongoDB error: ' . $e->getMessage();
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

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

    // --- Autres méthodes ---
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