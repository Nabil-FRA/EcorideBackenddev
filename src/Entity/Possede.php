<?php

namespace App\Entity;

use App\Repository\PossedeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PossedeRepository::class)]
class Possede
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Relation ManyToOne avec l'entité `Utilisateur`.
     * Une relation `Possede` est liée à un utilisateur.
     */
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'possedes', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] // Assurez la suppression en cascade si un utilisateur est supprimé
    private ?Utilisateur $utilisateur = null;

    /**
     * Relation ManyToOne avec l'entité `Role`.
     * Une relation `Possede` est liée à un rôle.
     */
    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'possedes', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] // Assurez la suppression en cascade si un rôle est supprimé
    private ?Role $role = null;

    // --------------------
    // GETTERS / SETTERS
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'utilisateur associé à cette relation.
     *
     * @return Utilisateur|null
     */
    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    /**
     * Définit l'utilisateur associé à cette relation.
     *
     * @param Utilisateur|null $utilisateur
     * @return $this
     */
    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Retourne le rôle associé à cette relation.
     *
     * @return Role|null
     */
    public function getRole(): ?Role
    {
        return $this->role;
    }

    /**
     * Définit le rôle associé à cette relation.
     *
     * @param Role|null $role
     * @return $this
     */
    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }
}
