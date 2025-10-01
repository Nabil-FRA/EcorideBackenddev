<?php

namespace App\Entity;

use App\Repository\UtiliseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UtiliseRepository::class)]
class Utilise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['covoiturage:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Voiture::class, inversedBy: 'utilises')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['covoiturage:read'])]
    private ?Voiture $voiture = null;

    #[ORM\ManyToOne(targetEntity: Covoiturage::class, inversedBy: 'utilise')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['covoiturage:read'])]
    private ?Covoiturage $covoiturage = null;

    // --------------------
    // GETTERS / SETTERS
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVoiture(): ?Voiture
    {
        return $this->voiture;
    }

    public function setVoiture(?Voiture $voiture): self
    {
        $this->voiture = $voiture;

        return $this;
    }

    public function getCovoiturage(): ?Covoiturage
    {
        return $this->covoiturage;
    }

    public function setCovoiturage(?Covoiturage $covoiturage): self
    {
        $this->covoiturage = $covoiturage;

        return $this;
    }
}
