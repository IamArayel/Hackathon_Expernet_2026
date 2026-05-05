<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testLoginPageLoads(): void
    {
        $this->client->request('GET', '/auth/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRegisterPageLoads(): void
    {
        $this->client->request('GET', '/auth/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRootRedirectsToLogin(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseRedirects('/auth/login');
    }

    public function testAdminRequiresAuthentication(): void
    {
        $this->client->request('GET', '/admin');

        $this->assertResponseRedirects('/auth/login');
    }
}
