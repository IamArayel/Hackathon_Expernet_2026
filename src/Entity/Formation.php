<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['beginner', 'intermediate', 'advanced'])]
    private string $difficulty = 'beginner';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Module::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $modules;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getDifficulty(): string { return $this->difficulty; }
    public function setDifficulty(string $difficulty): static { $this->difficulty = $difficulty; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getDifficultyMultiplier(): float
    {
        return match($this->difficulty) {
            'intermediate' => 1.5,
            'advanced' => 2.0,
            default => 1.0,
        };
    }

    public function getModules(): Collection { return $this->modules; }
    public function addModule(Module $module): static
    {
        if (!$this->modules->contains($module)) {
            $this->modules->add($module);
            $module->setFormation($this);
        }
        return $this;
    }
    public function removeModule(Module $module): static
    {
        if ($this->modules->removeElement($module)) {
            if ($module->getFormation() === $this) {
                $module->setFormation(null);
            }
        }
        return $this;
    }
}
