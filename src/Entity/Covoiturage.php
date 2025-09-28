<?php

namespace App\Entity;

use App\Repository\CovoiturageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CovoiturageRepository::class)]
class Covoiturage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['covoiturage:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['covoiturage:read'])]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(type: 'time')]
    #[Groups(['covoiturage:read'])]
    private ?\DateTimeInterface $heureDepart = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['covoiturage:read'])]
    private ?\DateTimeInterface $dateArrivee = null;

    #[ORM\Column(type: 'time')]
    #[Groups(['covoiturage:read'])]
    private ?\DateTimeInterface $heureArrivee = null;

    #[ORM\Column(length: 50)]
    #[Groups(['covoiturage:read'])]
    private ?string $lieuDepart = null;

    #[ORM\Column(length: 50)]
    #[Groups(['covoiturage:read'])]
    private ?string $lieuArrivee = null;

    #[ORM\Column]
    #[Groups(['covoiturage:read'])]
    private ?int $nbPlace = null;

    #[ORM\Column]
    #[Groups(['covoiturage:read'])]
    private ?float $prixPersonne = null;

    #[ORM\Column(length: 50)]
    #[Groups(['covoiturage:read'])]
    private ?string $statut = null;

    #[ORM\OneToMany(mappedBy: 'covoiturage', targetEntity: Participe::class, cascade: ['persist', 'remove'])]
    private Collection $participes;

    #[ORM\OneToMany(mappedBy: 'covoiturage', targetEntity: Utilise::class, cascade: ['persist', 'remove'])]
    private Collection $utilise;

    public function __construct()
    {
        $this->utilise = new ArrayCollection();
        $this->participes = new ArrayCollection();
    }

    // --------------------
    // GETTERS / SETTERS (votre code existant, inchangé)
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getHeureDepart(): ?\DateTimeInterface
    {
        return $this->heureDepart;
    }

    public function setHeureDepart(\DateTimeInterface $heureDepart): self
    {
        $this->heureDepart = $heureDepart;
        return $this;
    }

    public function getDateArrivee(): ?\DateTimeInterface
    {
        return $this->dateArrivee;
    }

    public function setDateArrivee(\DateTimeInterface $dateArrivee): self
    {
        $this->dateArrivee = $dateArrivee;
        return $this;
    }

    public function getHeureArrivee(): ?\DateTimeInterface
    {
        return $this->heureArrivee;
    }

    public function setHeureArrivee(\DateTimeInterface $heureArrivee): self
    {
        $this->heureArrivee = $heureArrivee;
        return $this;
    }

    public function getLieuDepart(): ?string
    {
        return $this->lieuDepart;
    }

    public function setLieuDepart(string $lieuDepart): self
    {
        $this->lieuDepart = $lieuDepart;
        return $this;
    }

    public function getLieuArrivee(): ?string
    {
        return $this->lieuArrivee;
    }

    public function setLieuArrivee(string $lieuArrivee): self
    {
        $this->lieuArrivee = $lieuArrivee;
        return $this;
    }

    public function getNbPlace(): ?int
    {
        return $this->nbPlace;
    }

    public function setNbPlace(int $nbPlace): self
    {
        $this->nbPlace = $nbPlace;
        return $this;
    }

    public function getPrixPersonne(): ?float
    {
        return $this->prixPersonne;
    }

    public function setPrixPersonne(float $prixPersonne): self
    {
        $this->prixPersonne = $prixPersonne;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * @return Collection<int, Utilise>
     */
    public function getUtilise(): Collection
    {
        return $this->utilise;
    }

    public function addUtilise(Utilise $utilise): self
    {
        if (!$this->utilise->contains($utilise)) {
            $this->utilise->add($utilise);
            $utilise->setCovoiturage($this);
        }
        return $this;
    }

    public function removeUtilise(Utilise $utilise): self
    {
        if ($this->utilise->removeElement($utilise)) {
            if ($utilise->getCovoiturage() === $this) {
                $utilise->setCovoiturage(null);
            }
        }
        return $this;
    }
    
    /**
     * @return Collection<int, Participe>
     */
    public function getParticipes(): Collection
    {
        return $this->participes;
    }
    
    public function addParticipe(Participe $participe): self
    {
        if (!$this->participes->contains($participe)) {
            $this->participes->add($participe);
            $participe->setCovoiturage($this);
        }
        return $this;
    }
    
    public function removeParticipe(Participe $participe): self
    {
        if ($this->participes->removeElement($participe)) {
            if ($participe->getCovoiturage() === $this) {
                $participe->setCovoiturage(null);
            }
        }
        return $this;
    }

    // ===================================================================
    // MÉTHODE AJOUTÉE (requise par CovoiturageController)
    // ===================================================================

    /**
     * Méthode "helper" pour retrouver facilement l'utilisateur qui est le chauffeur.
     * Cette logique suppose que le premier participant enregistré est le chauffeur.
     * Adaptez-la si votre logique métier est différente.
     */
    public function getChauffeur(): ?Utilisateur
    {
        if (!$this->getParticipes()->isEmpty()) {
            // Renvoie l'utilisateur de la première entité Participe de la collection
            return $this->getParticipes()->first()->getUtilisateur();
        }

        return null;
    }
}