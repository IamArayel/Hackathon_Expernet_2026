<?php

namespace App\Service;

use App\Entity\Module;
use App\Entity\User;
use App\Repository\BadgeRepository;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    private const XP_BASE = 50;
    private const XP_PER_LEVEL = 200;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BadgeRepository $badgeRepository,
    ) {}

    public function rewardModuleCompletion(User $user, Module $module, int $score): int
    {
        $xp = (int) round(self::XP_BASE * ($score / 100) * $module->getFormation()->getDifficultyMultiplier());
        $xp = max(10, $xp);

        $user->addXp($xp);
        $this->updateLevel($user);
        $this->checkAndAwardBadges($user);

        return $xp;
    }

    public function updateLevel(User $user): void
    {
        $newLevel = (int) floor($user->getXp() / self::XP_PER_LEVEL) + 1;
        if ($newLevel > $user->getLevel()) {
            $user->setLevel($newLevel);
        }
    }

    private function checkAndAwardBadges(User $user): void
    {
        $allBadges = $this->badgeRepository->findAll();

        foreach ($allBadges as $badge) {
            if ($user->getBadges()->contains($badge)) {
                continue;
            }

            $criteria = $badge->getCriteria();
            $earned = match($criteria['type'] ?? '') {
                'xp' => $user->getXp() >= ($criteria['value'] ?? PHP_INT_MAX),
                'level' => $user->getLevel() >= ($criteria['value'] ?? PHP_INT_MAX),
                default => false,
            };

            if ($earned) {
                $user->addBadge($badge);
            }
        }
    }

    public function xpToNextLevel(User $user): int
    {
        return ($user->getLevel() * self::XP_PER_LEVEL) - $user->getXp();
    }

    public function levelProgressPercent(User $user): int
    {
        $xpInLevel = $user->getXp() % self::XP_PER_LEVEL;
        return (int) round(($xpInLevel / self::XP_PER_LEVEL) * 100);
    }
}
