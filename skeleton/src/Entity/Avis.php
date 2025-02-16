<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['avis:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['avis:read', 'avis:write'])]
    private ?string $commentaire = null;

    #[ORM\Column(length: 50)]
    #[Groups(['avis:read', 'avis:write'])]
    private ?string $note = null;

    #[ORM\Column(length: 50)]
    #[Groups(['avis:read', 'avis:write'])]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['avis:read', 'avis:write'])]
    private ?Depose $depose = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDepose(): ?Depose
    {
        return $this->depose;
    }

    public function setDepose(?Depose $depose): static
    {
        $this->depose = $depose;

        return $this;
    }
}
