<?php

namespace App\Entity;

use App\Repository\MarqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MarqueRepository::class)]
class Marque
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // ID : uniquement en lecture, puisqu’il est auto-généré
    #[Groups(['marque:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['marque:read', 'marque:write'])]  // libelle sera visible en lecture et modifiable en écriture
    private ?string $libelle = null;

    /**
     * Relation OneToMany vers Detient
     *
     * @var Collection<int, Detient>
     */
    #[ORM\OneToMany(targetEntity: Detient::class, mappedBy: 'marque', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['marque:read', 'marque:write'])]
    private Collection $detients;

    public function __construct()
    {
        $this->detients = new ArrayCollection();
    }

    // --- GETTERS / SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    /**
     * @return Collection<int, Detient>
     */
    public function getDetients(): Collection
    {
        return $this->detients;
    }

    public function addDetient(Detient $detient): static
    {
        if (!$this->detients->contains($detient)) {
            $this->detients->add($detient);
            $detient->setMarque($this);
        }

        return $this;
    }

    public function removeDetient(Detient $detient): static
    {
        if ($this->detients->removeElement($detient)) {
            // set the owning side to null (unless already changed)
            if ($detient->getMarque() === $this) {
                $detient->setMarque(null);
            }
        }

        return $this;
    }
}
