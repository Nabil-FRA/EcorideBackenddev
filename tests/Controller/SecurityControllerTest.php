<?php
namespace App\Tests\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    /**
     * Teste une connexion réussie
     */
    public function testLoginSuccessfully(): void
    {
        $client = static::createClient();

        // Les données de l'utilisateur créé par UserFixtures
        $credentials = [
            'email' => 'admin@ecoride.com',
            'password' => 'adminpass',
        ];

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($credentials)
        );

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('apiToken', $response);
        $this->assertNotEmpty($response['apiToken']);
    }

    /**
     * Teste une connexion avec un mauvais mot de passe
     */
    public function testLoginWithWrongPassword(): void
    {
        $client = static::createClient();

        $credentials = [
            'email' => 'admin@ecoride.com',
            'password' => 'wrongpassword', // Mot de passe incorrect
        ];

        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($credentials));

        $this->assertResponseStatusCodeSame(401); // Unauthorized
    }

    /**
     * Teste une connexion avec un utilisateur qui n'existe pas
     */
    public function testLoginWithUnknownUser(): void
    {
        $client = static::createClient();

        $credentials = [
            'email' => 'unknown@example.com', // Email inconnu
            'password' => 'password',
        ];

        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($credentials));

        $this->assertResponseStatusCodeSame(404); // Not Found
    }

    /**
     * Teste une connexion avec un utilisateur désactivé
     */
    public function testLoginWithInactiveUser(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // On désactive un utilisateur de test
        $testUser = $userRepository->findOneBy(['email' => 'user-0@ecoride.com']); // Assumant que vos fixtures créent cet utilisateur
        $testUser->setIsActive(false);
        $entityManager->flush();

        $credentials = [
            'email' => 'user-0@ecoride.com',
            'password' => 'userpass',
        ];

        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($credentials));

        $this->assertResponseStatusCodeSame(403); // Forbidden
        
        // Réactiver l'utilisateur pour ne pas affecter les autres tests
        $testUser->setIsActive(true);
        $entityManager->flush();
    }
}