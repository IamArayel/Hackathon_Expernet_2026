<?php

namespace App\Service;

use App\Entity\Module;
use App\Entity\User;
use App\Repository\BadgeRepository;
use Psr\Log\LoggerInterface;

class GamificationService
{
    private const XP_BASE = 50;
    private const XP_PER_LEVEL = 200;

    public function __construct(
        private readonly BadgeRepository $badgeRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function rewardModuleCompletion(User $user, Module $module, int $score): int
    {
        $xp = (int) round(self::XP_BASE * ($score / 100) * $module->getFormation()->getDifficultyMultiplier());
        $xp = max(10, $xp);

        $user->addXp($xp);
        $this->updateLevel($user);
        $this->checkAndAwardBadges($user);

        $this->logger->info('Module completed', [
            'user'      => $user->getEmail(),
            'module_id' => $module->getId(),
            'score'     => $score,
            'xp_gained' => $xp,
            'level'     => $user->getLevel(),
            'total_xp'  => $user->getXp(),
        ]);

        return $xp;
    }

    public function updateLevel(User $user): void
    {
        $newLevel = (int) floor($user->getXp() / self::XP_PER_LEVEL) + 1;
        if ($newLevel > $user->getLevel()) {
            $user->setLevel($newLevel);
            $this->logger->info('Level up', [
                'user'  => $user->getEmail(),
                'level' => $newLevel,
            ]);
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
                $this->logger->info('Badge awarded', [
                    'user'  => $user->getEmail(),
                    'badge' => $badge->getName(),
                ]);
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
