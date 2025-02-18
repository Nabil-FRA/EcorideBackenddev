<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function testApiAccountUrlIsSecure(): void
    {
        $client = self::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/api/account/me');

        self::assertResponseStatusCodeSame(401);
    }
}