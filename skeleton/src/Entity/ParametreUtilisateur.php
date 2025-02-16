<?php

namespace App\Entity;

use App\Repository\ParametreUtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ParametreUtilisateurRepository::class)]
class ParametreUtilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['parametre_utilisateur:read'])]
    private ?int $id = null;

    /**
     * Relation ManyToOne avec l'entité `Utilisateur`.
     * Plusieurs configurations peuvent être liées à un utilisateur.
     */
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'parametresUtilisateurs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['parametre_utilisateur:read', 'parametre_utilisateur:write'])]
    private ?Utilisateur $utilisateur = null;

    /**
     * Relation ManyToOne avec l'entité `Configuration`.
     * Plusieurs utilisateurs peuvent avoir la même configuration.
     */
    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'parametreUtilisateur')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['parametre_utilisateur:read', 'parametre_utilisateur:write'])]
    private ?Configuration $configuration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }
}
