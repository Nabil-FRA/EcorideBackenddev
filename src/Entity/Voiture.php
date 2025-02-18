<?php

namespace App\Entity;

use App\Repository\VoitureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VoitureRepository::class)]
class Voiture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['covoiturage:read'])]
    private ?string $modele = null;

    #[ORM\Column(length: 50)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 50)]
    #[Groups(['covoiturage:read'])]
    private ?string $energie = null;

    #[ORM\Column(length: 50)]
    private ?string $couleur = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $datePremiereImmatriculation = null;

    #[ORM\OneToMany(mappedBy: 'voiture', targetEntity: Utilise::class, cascade: ['persist', 'remove'])]
    private Collection $utilises;

    #[ORM\OneToOne(mappedBy: 'voiture', targetEntity: Gere::class, cascade: ['persist', 'remove'])]
    private ?Gere $gere = null;

    #[ORM\OneToOne(targetEntity: Detient::class, mappedBy: 'voiture', cascade: ['persist', 'remove'])]
private ?Detient $detient = null;


    public function __construct()
    {
        $this->utilises = new ArrayCollection();
    }

    // --------------------
    // GETTERS / SETTERS
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): self
    {
        $this->modele = $modele;

        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(string $energie): self
    {
        $this->energie = $energie;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): self
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getDatePremiereImmatriculation(): ?\DateTimeImmutable
    {
        return $this->datePremiereImmatriculation;
    }

    public function setDatePremiereImmatriculation(?\DateTimeImmutable $datePremiereImmatriculation): self
    {
        $this->datePremiereImmatriculation = $datePremiereImmatriculation;

        return $this;
    }

    /**
     * @return Collection<int, Utilise>
     */
    public function getUtilises(): Collection
    {
        return $this->utilises;
    }

    public function addUtilise(Utilise $utilise): self
    {
        if (!$this->utilises->contains($utilise)) {
            $this->utilises->add($utilise);
            $utilise->setVoiture($this);
        }

        return $this;
    }

    public function removeUtilise(Utilise $utilise): self
    {
        if ($this->utilises->removeElement($utilise)) {
            if ($utilise->getVoiture() === $this) {
                $utilise->setVoiture(null);
            }
        }

        return $this;
    }

    public function getGere(): ?Gere
    {
        return $this->gere;
    }

    public function setGere(?Gere $gere): self
    {
        $this->gere = $gere;

        return $this;
    }

    public function getDetient(): ?Detient
    {
        return $this->detient;
    }
    
    public function setDetient(?Detient $detient): self
    {
        $this->detient = $detient;
        
        // Assure la relation bidirectionnelle
        if ($detient !== null && $detient->getVoiture() !== $this) {
            $detient->setVoiture($this);
        }
        
        return $this;
    }
    
    public function getMarque(): ?Marque
    {
        return $this->detient?->getMarque();
    }
}
