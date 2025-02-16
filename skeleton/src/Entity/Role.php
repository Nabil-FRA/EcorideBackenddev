<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['role:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['role:read', 'role:write'])]
    private ?string $libelle = null;

    /**
     * Relation OneToMany avec l'entité `Possede`.
     * Un rôle peut être possédé par plusieurs utilisateurs via la table de liaison `possede`.
     *
     * @var Collection<int, Possede>
     */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: Possede::class, cascade: ['persist', 'remove'])]
    private Collection $possedes;

    public function __construct()
    {
        $this->possedes = new ArrayCollection();
    }

    // --------------------
    // GETTERS / SETTERS
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * Retourne la collection des relations `Possede` liées à ce rôle.
     *
     * @return Collection<int, Possede>
     */
    public function getPossedes(): Collection
    {
        return $this->possedes;
    }

    /**
     * Ajoute une relation `Possede` à ce rôle.
     *
     * @param Possede $possede
     * @return $this
     */
    public function addPossede(Possede $possede): self
    {
        if (!$this->possedes->contains($possede)) {
            $this->possedes->add($possede);
            $possede->setRole($this);
        }

        return $this;
    }

    /**
     * Supprime une relation `Possede` de ce rôle.
     *
     * @param Possede $possede
     * @return $this
     */
    public function removePossede(Possede $possede): self
    {
        if ($this->possedes->removeElement($possede)) {
            // Supprime la relation côté `Possede`
            if ($possede->getRole() === $this) {
                $possede->setRole(null);
            }
        }

        return $this;
    }
}
