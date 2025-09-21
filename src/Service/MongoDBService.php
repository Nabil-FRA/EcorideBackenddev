<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version optimisée pour Heroku + Fixie SOCKS5 proxy + MongoDB Atlas
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
    private array $driverOptions = [];

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
        
        // Configuration automatique du proxy Fixie SOCKS5
        $this->configureFixieProxy();
        
        if ($this->logger) {
            $this->logger->info('MongoDBService initialized', [
                'database' => $database,
                'uri_prefix' => substr($this->uri, 0, 50) . '...',
                'timeouts_added' => true,
                'fixie_proxy_enabled' => !empty($this->driverOptions),
                'driver_options_count' => count($this->driverOptions)
            ]);
        }
    }

    /**
     * Configure automatiquement le proxy Fixie SOCKS5
     * 
     * @return void
     */
    private function configureFixieProxy(): void
    {
        // Récupère l'URL du proxy Fixie depuis les variables d'environnement Heroku
        $proxyUrl = getenv('FIXIE_URL');
        
        if (empty($proxyUrl)) {
            if ($this->logger) {
                $this->logger->debug('No Fixie proxy URL found in environment');
            }
            return;
        }

        if ($this->logger) {
            $this->logger->info('Fixie SOCKS5 proxy detected, configuring MongoDB driver', [
                'proxy_url_set' => true,
                'proxy_url_prefix' => substr($proxyUrl, 0, 30) . '...'
            ]);
        }

        // Parser l'URL Fixie (format: socks5://user:pass@host:port)
        $proxyParts = parse_url($proxyUrl);
        
        if (!$proxyParts || empty($proxyParts['host']) || empty($proxyParts['port'])) {
            if ($this->logger) {
                $this->logger->warning('FIXIE_URL malformed, cannot parse proxy configuration', [
                    'proxy_url' => $proxyUrl,
                    'parsed_parts' => $proxyParts
                ]);
            }
            return;
        }

        $proxyHost = $proxyParts['host'];
        $proxyPort = $proxyParts['port'];
        $proxyUser = $proxyParts['user'] ?? null;
        $proxyPass = $proxyParts['pass'] ?? null;

        // Configuration du driver MongoDB pour SOCKS5
        $this->driverOptions = [
            // Configuration du driver pour utiliser le proxy SOCKS5
            'uri' => $this->uri,
            // Options spécifiques pour le proxy
            'proxy' => [
                'type' => 'socks5',
                'host' => $proxyHost,
                'port' => $proxyPort,
                'username' => $proxyUser,
                'password' => $proxyPass,
                // Configuration SSL/TLS via proxy
                'ssl' => [
                    'allow_self_signed_certificate' => false,
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_invalid_hostnames' => false,
                    'crypto_method' => \MONGODB_CRYPTO_METHOD_SSLv1_2_CLIENT
                ]
            ],
            // Options de connexion optimisées pour proxy
            'connectTimeoutMS' => 60000,           // 60s
            'socketTimeoutMS' => 90000,            // 90s
            'serverSelectionTimeoutMS' => 45000,   // 45s
            'heartbeatFrequencyMS' => 5000,        // Check toutes les 5s
            'maxIdleTimeMS' => 0,                  // Pas de timeout idle
            'maxPoolSize' => 5,                    // Pool réduit pour Heroku
            'minPoolSize' => 1,                    // 1 connexion minimum
            'maxConnecting' => 2,                  // 2 connexions simultanées
            'waitQueueTimeoutMS' => 60000,         // 60s pour queue
            'maxTimeMS' => 60000,                  // 60s max par opération
            // Options de retry pour proxy
            'retryWrites' => true,
            'retryReads' => true,
            'retryableWrites' => true,
            'w' => 'majority',
            // Configuration SSL/TLS complète
            'ssl' => [
                'allow_self_signed_certificate' => false,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_invalid_hostnames' => false,
                'ca_file' => null,  // Utiliser les CA système
                'crypto_method' => \MONGODB_CRYPTO_METHOD_SSLv1_2_CLIENT
            ]
        ];

        if ($this->logger) {
            $this->logger->info('Fixie SOCKS5 proxy configured successfully', [
                'proxy_host' => $proxyHost,
                'proxy_port' => $proxyPort,
                'proxy_auth_enabled' => !empty($proxyUser),
                'driver_options_keys' => array_keys($this->driverOptions)
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
        
        // Paramètres de base
        $baseParams = [
            'retryWrites=true',
            'w=majority',
            'appName=Ecoride-Heroku-Fixie'
        ];
        
        // Paramètres de timeout (déjà inclus dans driverOptions, mais pour compatibilité)
        $timeoutParams = [
            'connectTimeoutMS=60000',
            'socketTimeoutMS=90000',
            'serverSelectionTimeoutMS=45000'
        ];
        
        return $uri . $separator . implode('&', array_merge($baseParams, $timeoutParams));
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
     * Version optimisée pour Heroku avec Fixie SOCKS5 proxy
     * 
     * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);
        
        try {
            if ($this->logger) {
                $this->logger->info('Establishing MongoDB connection via Fixie SOCKS5', [
                    'uri_prefix' => substr($this->uri, 0, 40) . '...',
                    'database' => $this->databaseName,
                    'proxy_enabled' => !empty($this->driverOptions),
                    'connect_timeout' => 60,
                    'socket_timeout' => 90
                ]);
            }

            // === CONNEXION AVEC PROXY FIXIE ===
            // Utilisation des driverOptions pour SOCKS5
            $this->client = new Client($this->uri, [], $this->driverOptions);
            
            // Sélection de la base de données
            $this->database = $this->client->selectDatabase($this->databaseName);
            
            // Test de connexion simple via proxy
            $this->testConnectionThroughProxy();
            
            // === CONNEXION RÉUSSIE ===
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully via Fixie', [
                    'database' => $this->databaseName,
                    'connection_time_ms' => $connectionTime,
                    'client_class' => get_class($this->client),
                    'database_class' => get_class($this->database),
                    'proxy_via_fixie' => true
                ]);
            }
            
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            $errorMsg = 'MongoDB connection timeout (60s) via Fixie - Vérifiez la connectivité proxy';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
            $errorMsg = 'MongoDB server selection timeout (45s) via Fixie - Vérifiez le cluster Atlas';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\AuthenticationException $e) {
            $errorMsg = 'MongoDB authentication failed via Fixie - Vérifiez les identifiants MONGODB_URI';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            $errorMsg = 'MongoDB bulk write failed via Fixie - Vérifiez les privilèges utilisateur';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\Exception $e) {
            $errorMsg = 'Unexpected MongoDB error via Fixie: ' . $e->getMessage();
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
        }
    }

    /**
     * Test de connexion spécifique pour proxy Fixie
     * 
     * @return void
     * @throws \RuntimeException Si le test échoue
     */
    private function testConnectionThroughProxy(): void
    {
        try {
            // Test simple : ping sur la base admin
            $adminDb = $this->client->selectDatabase('admin');
            $pingResult = $adminDb->runCommand(['ping' => 1]);
            
            if (!isset($pingResult['ok']) || $pingResult['ok'] != 1) {
                throw new \RuntimeException('MongoDB ping failed via Fixie: ' . json_encode($pingResult));
            }
            
            if ($this->logger) {
                $this->logger->debug('MongoDB ping successful via Fixie SOCKS5', [
                    'ping_result' => $pingResult
                ]);
            }
            
            // Test d'écriture simple
            $testCollection = $this->database->selectCollection('__proxy_test__');
            $testDoc = [
                'test_proxy_connection' => true,
                'via_fixie' => true,
                'timestamp' => new \DateTime('now'),
                'server' => php_uname('n')
            ];
            
            $insertResult = $testCollection->insertOne($testDoc);
            
            if (!$insertResult->getInsertedId()) {
                throw new \RuntimeException('Failed to insert test document via Fixie');
            }
            
            // Vérification lecture
            $readResult = $testCollection->findOne(['_id' => $insertResult->getInsertedId()]);
            if (!$readResult || $readResult['test_proxy_connection'] !== true) {
                throw new \RuntimeException('Failed to read test document via Fixie');
            }
            
            // Nettoyage
            $testCollection->deleteOne(['_id' => $insertResult->getInsertedId()]);
            
            if ($this->logger) {
                $this->logger->debug('Proxy connection test completed successfully', [
                    'test_document_id' => $insertResult->getInsertedId(),
                    'read_success' => true
                ]);
            }
            
        } catch (\Exception $e) {
            $errorMsg = 'Proxy connection test failed via Fixie: ' . $e->getMessage();
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
                'fixie_proxy_used' => !empty($this->driverOptions)
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
            $this->logger->debug('MongoDB connection closed via Fixie', [
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
     * Teste la connexion MongoDB (health check)
     * 
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->isConnected()) {
                $this->getDatabase();
            }
            
            // Ping simple via proxy
            $adminDb = $this->client->selectDatabase('admin');
            $pingResult = $adminDb->runCommand(['ping' => 1]);
            
            return isset($pingResult['ok']) && $pingResult['ok'] == 1;
            
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->warning('MongoDB health check failed via Fixie', [
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
            'fixie_proxy_enabled' => !empty($this->driverOptions),
            'client_class' => $this->client ? get_class($this->client) : null,
            'database_class' => $this->database ? get_class($this->database) : null,
            'health_check' => $this->testConnection(),
            'driver_options' => array_keys($this->driverOptions)
        ];

        if ($this->isConnected()) {
            try {
                $info['server_info'] = $this->client->selectDatabase('admin')->runCommand(['ismaster' => 1]);
                $info['server_info']['via_proxy'] = true;
            } catch (\Exception $e) {
                $info['server_info'] = ['error' => $e->getMessage(), 'via_proxy' => true];
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
            'status' => $this->isConnected() ? 'connected_via_fixie' : 'disconnected',
            'database' => $this->getDatabaseName(),
            'connection_info' => $this->getConnectionInfo(),
            'timestamp' => new \DateTime('now'),
            'environment' => [
                'fixie_url_set' => !empty(getenv('FIXIE_URL')),
                'heroku_dyno' => getenv('HEROKU_DYNO'),
                'app_name' => getenv('APP_NAME') ?: 'local'
            ]
        ];
    }
}