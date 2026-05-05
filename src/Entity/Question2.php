<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
 


#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ApiResource]
class Question2
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $intitule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sous_texte = null;

    #[ORM\Column(type: 'json')]
    private array $choix = []; // Stockera ["Réponse A", "Réponse B", "Réponse C"]

    #[ORM\Column]
    private int $indexCorrect; // Stockera l'index (ex: 0)

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reponseUtilisateur = null;
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getSousTexte(): ?string
    {
        return $this->sous_texte;
    }

    public function setSousTexte(?string $sous_texte): static
    {
        $this->sous_texte = $sous_texte;

        return $this;
    }

    public function getReponseUtilisateur(): ?string
    {
        return $this->reponseUtilisateur;
    }

    public function setReponseUtilisateur(?string $reponseUtilisateur): static
    {
        $this->reponseUtilisateur = $reponseUtilisateur;

        return $this;
    }

    /**
     * Get the value of choix
     */ 
    public function getChoix()
    {
        return $this->choix;
    }

    /**
     * Set the value of choix
     *
     * @return  self
     */ 
    public function setChoix($choix)
    {
        $this->choix = $choix;

        return $this;
    }

    /**
     * Get the value of indexCorrect
     */ 
    public function getIndexCorrect()
    {
        return $this->indexCorrect;
    }

    /**
     * Set the value of indexCorrect
     *
     * @return  self
     */ 
    public function setIndexCorrect($indexCorrect)
    {
        $this->indexCorrect = $indexCorrect;

        return $this;
    }
}
