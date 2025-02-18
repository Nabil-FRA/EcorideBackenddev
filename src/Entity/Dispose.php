<?php

namespace App\Entity;

use App\Repository\DisposeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DisposeRepository::class)]
class Dispose
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'dispose', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parametre $parametre = null;

    #[ORM\ManyToOne(inversedBy: 'disposes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Configuration $configuration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParametre(): ?Parametre
    {
        return $this->parametre;
    }

    public function setParametre(?Parametre $parametre): static
    {
        $this->parametre = $parametre;

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
