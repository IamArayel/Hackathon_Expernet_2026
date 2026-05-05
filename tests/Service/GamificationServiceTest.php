<?php

namespace App\Tests\Service;

use App\Entity\Formation;
use App\Entity\Module;
use App\Entity\User;
use App\Repository\BadgeRepository;
use App\Service\GamificationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GamificationServiceTest extends TestCase
{
    private GamificationService $gamification;

    protected function setUp(): void
    {
        $badgeRepo = $this->createStub(BadgeRepository::class);
        $badgeRepo->method('findAll')->willReturn([]);

        $this->gamification = new GamificationService($badgeRepo, new NullLogger());
    }

    private function makeModule(string $difficulty = 'beginner'): Module
    {
        $formation = new Formation();
        $formation->setDifficulty($difficulty);

        $module = new Module();
        $module->setFormation($formation);
        $module->setTitle('Test module');

        return $module;
    }

    public function testRewardModuleCompletion_calculatesCorrectXp(): void
    {
        $user = new User();
        $module = $this->makeModule('beginner'); // multiplier 1.0

        $xp = $this->gamification->rewardModuleCompletion($user, $module, 100);

        $this->assertSame(50, $xp); // 50 * (100/100) * 1.0 = 50
    }

    public function testRewardModuleCompletion_appliesMultiplierForAdvanced(): void
    {
        $user = new User();
        $module = $this->makeModule('advanced'); // multiplier 2.0

        $xp = $this->gamification->rewardModuleCompletion($user, $module, 100);

        $this->assertSame(100, $xp); // 50 * 1.0 * 2.0 = 100
    }

    public function testRewardModuleCompletion_minimumXpIsApplied(): void
    {
        $user = new User();
        $module = $this->makeModule('beginner');

        $xp = $this->gamification->rewardModuleCompletion($user, $module, 0);

        $this->assertSame(10, $xp); // 0 XP calculé → minimum 10
    }

    public function testRewardModuleCompletion_addsXpToUser(): void
    {
        $user = new User();
        $module = $this->makeModule();

        $this->gamification->rewardModuleCompletion($user, $module, 100);

        $this->assertSame(50, $user->getXp());
    }

    public function testUpdateLevel_upgradesWhenThresholdReached(): void
    {
        $user = new User();
        $user->setXp(200); // seuil du niveau 2

        $this->gamification->updateLevel($user);

        $this->assertSame(2, $user->getLevel());
    }

    public function testUpdateLevel_doesNotDowngrade(): void
    {
        $user = new User();
        $user->setXp(50);
        $user->setLevel(5); // niveau manuellement élevé

        $this->gamification->updateLevel($user);

        $this->assertSame(5, $user->getLevel());
    }

    public function testXpToNextLevel_returnsCorrectAmount(): void
    {
        $user = new User();
        $user->setXp(50);
        $user->setLevel(1);

        $this->assertSame(150, $this->gamification->xpToNextLevel($user)); // 1*200 - 50
    }

    public function testLevelProgressPercent_returnsCorrectPercent(): void
    {
        $user = new User();
        $user->setXp(100);

        $this->assertSame(50, $this->gamification->levelProgressPercent($user)); // 100/200 = 50%
    }
}
