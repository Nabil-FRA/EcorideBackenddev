<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version optimisée pour Heroku et MongoDB Atlas
 * Inclus la configuration automatique du proxy Fixie (SOCKS5)
 * * @author Nabil
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
     * * @param string $uri Chaîne de connexion MongoDB (mongodb+srv://...)
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
     * * @param string $uri URI originale
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
     * * @return \MongoDB\Database
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
     * Version optimisée pour Heroku avec timeouts et proxy Fixie
     * * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);
        
        // =================== DÉBUT DE LA MODIFICATION POUR FIXIE ===================
        $driverOptions = [];
        // Récupère l'URL du proxy depuis les variables d'environnement de Heroku
        $proxyUrl = getenv('FIXIE_URL');

        // Si l'URL du proxy existe, on configure les options du driver MongoDB
        if ($proxyUrl) {
            if ($this->logger) {
                $this->logger->info('Fixie proxy detected, configuring SOCKS5 connection.', [
                    'proxy_url_set' => !empty($proxyUrl)
                ]);
            }
            
            $proxyParts = parse_url($proxyUrl);
            $proxyUser = $proxyParts['user'] ?? null;
            $proxyPass = $proxyParts['pass'] ?? null;
            $proxyHost = $proxyParts['host'] ?? null;
            $proxyPort = $proxyParts['port'] ?? null;

            // Fixie utilise le protocole SOCKS5. On construit les options pour le driver.
            if ($proxyHost && $proxyPort) {
                 $driverOptions = [
                    'driver' => [
                        'options' => [
                            'proxy' => 'socks5://' . $proxyHost . ':' . $proxyPort,
                            'proxy_username' => $proxyUser,
                            'proxy_password' => $proxyPass,
                        ]
                    ]
                ];
            } else {
                 if ($this->logger) {
                    $this->logger->warning('FIXIE_URL is set but could not be parsed correctly.', ['url' => $proxyUrl]);
                 }
            }
        }
        // =================== FIN DE LA MODIFICATION POUR FIXIE =====================
        
        try {
            if ($this->logger) {
                $this->logger->info('Establishing MongoDB connection...', [
                    'uri_prefix' => substr($this->uri, 0, 40) . '...',
                    'database' => $this->databaseName,
                    'proxy_enabled' => !empty($proxyUrl)
                ]);
            }

            // === CONNEXION AVEC PROXY ET TIMEOUTS ===
            // On passe le tableau $driverOptions au constructeur du Client
            $this->client = new Client($this->uri, [], $driverOptions);
            
            // Sélection de la base de données
            $this->database = $this->client->selectDatabase($this->databaseName);
            
            // Test de connexion pour forcer une exception en cas de problème
            $this->database->command(['ping' => 1]);
            
            // === CONNEXION RÉUSSIE ===
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully', [
                    'database' => $this->databaseName,
                    'connection_time_ms' => $connectionTime,
                ]);
            }
            
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            $errorMsg = 'MongoDB connection timeout (30s) - Vérifiez la connectivité réseau Heroku ↔ MongoDB Atlas';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
            
        } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
            $errorMsg = 'MongoDB server selection timeout (30s) - Vérifiez que le cluster MongoDB Atlas est en ligne';
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
     * Journalise une erreur MongoDB avec métriques de timing
     * * @param string $message Message d'erreur
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
            }
            
            if ($startTime > 0) {
                $context['connection_attempt_time'] = round((microtime(true) - $startTime) * 1000, 2) . 'ms';
            }
            
            $this->logger->error($message, $context);
        }
    }

    /**
     * Retourne le client MongoDB brut
     * * @return Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->connect();
        }
        
        return $this->client;
    }
    
    // ... (Le reste de vos méthodes reste inchangé)

    /**
     * Retourne l'URI de connexion MongoDB complète (avec timeouts)
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Retourne le nom de la base de données
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Ferme la connexion MongoDB
     * @return void
     */
    public function close(): void
    {
        $this->client = null;
        $this->database = null;
    }

    /**
     * Vérifie si la connexion MongoDB est active
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client !== null && $this->database !== null;
    }
}
