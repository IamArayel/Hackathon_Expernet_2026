<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BootPageTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin-test@example.com';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->createTestAdmin();
    }

    protected function tearDown(): void
    {
        $this->deleteTestAdmin();
        parent::tearDown();
    }

    private function createTestAdmin(): void
    {
        $container = static::getContainer();
        $repo = $container->get(UserRepository::class);

        if ($repo->findOneBy(['email' => self::ADMIN_EMAIL]) !== null) {
            return;
        }

        $em = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $admin = new User();
        $admin->setEmail(self::ADMIN_EMAIL)
            ->setUsername('admin-test')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($hasher->hashPassword($admin, 'password'));

        $em->persist($admin);
        $em->flush();
    }

    private function deleteTestAdmin(): void
    {
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $repo = $container->get(UserRepository::class);

        $admin = $repo->findOneBy(['email' => self::ADMIN_EMAIL]);
        if ($admin !== null) {
            $em->remove($admin);
            $em->flush();
        }
    }

    private function loginAsAdmin(): void
    {
        $admin = static::getContainer()
            ->get(UserRepository::class)
            ->findOneBy(['email' => self::ADMIN_EMAIL]);

        $this->client->loginUser($admin);
    }

    public function testPageAcceuil(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseRedirects('/auth/login');
    }

    public function testAdminDashboardAccessDenied(): void
    {
        $this->client->request('GET', '/admin');

        $this->assertResponseRedirects('/auth/login');
    }

    public function testAdminDashboardAccess(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
    }

    public function testAiSettingsPage(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/ai');

        $this->assertResponseIsSuccessful();
    }

    public function testAiSettingsSubmit(): void
    {
        $this->loginAsAdmin();
        $this->client->request('POST', '/admin/ai', [
            'ai_settings' => [
                'api_key' => 'test-key',
                'model' => 'google/gemma-2-2b-it',
                'system_prompt' => 'test prompt',
            ],
        ]);

        $this->assertResponseRedirects('/admin/ai');
    }
}
