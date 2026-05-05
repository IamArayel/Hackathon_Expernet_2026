<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(type: Types::JSON)]
    private array $options = [];

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $correctAnswer = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['mcq', 'open'])]
    private string $type = 'mcq';

    #[ORM\Column(default: 1)]
    #[Assert\Range(min: 1, max: 3)]
    private int $difficulty = 1;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Module $module = null;

    public function getId(): ?int { return $this->id; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getOptions(): array { return $this->options; }
    public function setOptions(array $options): static { $this->options = $options; return $this; }

    public function getCorrectAnswer(): ?string { return $this->correctAnswer; }
    public function setCorrectAnswer(string $correctAnswer): static { $this->correctAnswer = $correctAnswer; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getDifficulty(): int { return $this->difficulty; }
    public function setDifficulty(int $difficulty): static { $this->difficulty = $difficulty; return $this; }

    public function getModule(): ?Module { return $this->module; }
    public function setModule(?Module $module): static { $this->module = $module; return $this; }
}
