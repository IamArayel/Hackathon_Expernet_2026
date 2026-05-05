<?php

namespace App\Entity;

use App\Repository\UserProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProgressRepository::class)]
#[ORM\UniqueConstraint(name: 'user_module_unique', columns: ['user_id', 'module_id'])]
class UserProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'progress')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Module $module = null;

    #[ORM\Column(default: false)]
    private bool $completed = false;

    #[ORM\Column(default: 0)]
    private int $score = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getModule(): ?Module { return $this->module; }
    public function setModule(?Module $module): static { $this->module = $module; return $this; }

    public function isCompleted(): bool { return $this->completed; }
    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;
        if ($completed && $this->completedAt === null) {
            $this->completedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getScore(): int { return $this->score; }
    public function setScore(int $score): static { $this->score = $score; return $this; }

    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function getStartedAt(): \DateTimeImmutable { return $this->startedAt; }
}
