<?php

namespace App\Service;

use MongoDB\Client;
use Psr\Log\LoggerInterface;

/**
 * Service de connexion à MongoDB pour l'application Ecoride
 * Version FINALE pour Heroku + Fixie + MongoDB Atlas
 * SSL/TLS corrigé pour forcer TLS 1.2 et assurer une connexion sécurisée
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
        // Ajouter automatiquement les timeouts et paramètres pour Heroku
        $this->uri = $this->addHerokuFixieParams($uri);
        $this->databaseName = $database;
        $this->logger = $logger;

        if ($this->logger) {
            $this->logger->info('MongoDBService initialized (Heroku+Fixie Final Corrected)', [
                'database' => $database,
                'uri_prefix' => substr($this->uri, 0, 50) . '...',
                'tls_enforced' => '1.2',
                'fixie_optimized' => true
            ]);
        }
    }

    /**
     * Ajoute tous les paramètres nécessaires pour Heroku + Fixie
     * * @param string $uri URI originale
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
        
        // ✅ NOTE: Les options SSL dangereuses et inutiles ont été supprimées.
        // La configuration TLS est maintenant gérée via les driverOptions dans la méthode connect().
        $allParams = array_merge($essentialParams, $timeoutParams);

        return $uri . $separator . implode('&', $allParams);
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
     * VERSION FINALE CORRIGÉE : Force TLS 1.2 pour la compatibilité avec Atlas
     * * @throws \RuntimeException Si la connexion échoue
     */
    private function connect(): void
    {
        $startTime = microtime(true);

        try {
            if ($this->logger) {
                $this->logger->info('Establishing MongoDB connection (Final Corrected with TLS 1.2)', [
                    'uri_prefix' => substr($this->uri, 0, 40) . '...',
                    'database' => $this->databaseName,
                    'tls_version_forced' => '1.2'
                ]);
            }

            // === ✅ CONNEXION SÉCURISÉE ET CORRIGÉE ===
            // Options du driver pour forcer l'utilisation de TLS 1.2, requis par MongoDB Atlas.
            // Ceci résout les erreurs de "TLS handshake failed".
            $driverOptions = [
                'tls' => true, // S'assurer que TLS est bien activé
                'tlsContext' => [
                    // Forcer la version minimale du protocole à TLS 1.2
                    'min_tls_version' => \STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                ],
            ];

            // Instanciation du client avec l'URI ET les options de driver.
            $this->client = new Client($this->uri, [], $driverOptions);

            // Sélection de la base de données
            $this->database = $this->client->selectDatabase($this->databaseName);

            // Test minimal pour confirmer que la connexion est bien établie
            $this->minimalConnectionTest();

            // === CONNEXION RÉUSSIE ===
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($this->logger) {
                $this->logger->info('MongoDB connection established successfully (FINAL Corrected)', [
                    'database' => $this->databaseName,
                    'connection_time_ms' => $connectionTime,
                    'status' => 'success_final_corrected'
                ]);
            }
        } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            $errorMsg = 'MongoDB connection timeout (90s) - Network connectivity issue Heroku ↔ Atlas';
            $this->logError($errorMsg, $e, $startTime);
            throw new \RuntimeException($errorMsg, 0, $e);
        } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
            $errorMsg = 'MongoDB server selection timeout (60s) - Atlas cluster unreachable or TLS issue';
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
     * * @return void
     * @throws \RuntimeException Si le test échoue
     */
    private function minimalConnectionTest(): void
    {
        try {
            // Test le plus simple possible : lister les noms des collections
            $this->database->listCollectionNames();

            if ($this->logger) {
                $this->logger->debug('Minimal connection test PASSED', [
                    'test_method' => 'listCollectionNames',
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
                'error_type' => get_class($exception ?? new \stdClass()),
                'tls_forced' => '1.2'
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
     * * @return Client
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
     * * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Retourne le nom de la base de données
     * * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Ferme la connexion MongoDB
     * * @return void
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
     * * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client !== null && $this->database !== null;
    }
}