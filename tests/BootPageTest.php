<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BootPageTest extends WebTestCase
{
    public function testPageAcceuil(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseRedirects('/auth/login');
      
    }
    public function testAdminDashboardAccessDenied(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(302); // redirection login
    }
    public function testAdminDashboardAccess(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);

        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
    }
    public function testAiSettingsPage(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
        ]);

        $client->request('GET', '/admin/ai');

        $this->assertResponseIsSuccessful();
    }
    public function testAiSettingsSubmit(): void
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
        ]);

        $client->request('POST', '/admin/ai', [
            'api_key' => 'test-key',
            'model' => 'google/gemma-2-2b-it',
            'system_prompt' => 'test prompt',
        ]);

        $this->assertResponseRedirects('/admin/ai');
    }
}
