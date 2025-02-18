<?php

namespace App\Entity;

use App\Repository\DeposeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeposeRepository::class)]
class Depose
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Relation `OneToMany` avec l'entité `Avis`. 
     * Une `Depose` peut avoir plusieurs `Avis`.
     */
    #[ORM\OneToMany(mappedBy: 'depose', targetEntity: Avis::class, cascade: ['persist', 'remove'])]
    private Collection $avis;

    /**
     * Relation `ManyToOne` avec l'entité `Utilisateur`.
     * Une `Depose` est liée à un seul `Utilisateur`.
     */
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'deposes')]
    #[ORM\JoinColumn(nullable: false)] // Rendre cette relation obligatoire
    private ?Utilisateur $utilisateur = null;

    public function __construct()
    {
        $this->avis = new ArrayCollection();
    }

    // --------------------
    // GETTERS / SETTERS
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne la collection d'avis liés à cette dépose.
     *
     * @return Collection<int, Avis>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    /**
     * Ajoute un avis à la dépose.
     *
     * @param Avis $avi
     * @return $this
     */
    public function addAvi(Avis $avi): self
    {
        if (!$this->avis->contains($avi)) {
            $this->avis->add($avi);
            $avi->setDepose($this); // Définit la relation inverse
        }

        return $this;
    }

    /**
     * Supprime un avis de la dépose.
     *
     * @param Avis $avi
     * @return $this
     */
    public function removeAvi(Avis $avi): self
    {
        if ($this->avis->removeElement($avi)) {
            // Supprime la relation côté `Avis`
            if ($avi->getDepose() === $this) {
                $avi->setDepose(null);
            }
        }

        return $this;
    }

    /**
     * Retourne l'utilisateur lié à cette dépose.
     *
     * @return Utilisateur|null
     */
    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    /**
     * Définit l'utilisateur lié à cette dépose.
     *
     * @param Utilisateur|null $utilisateur
     * @return $this
     */
    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
