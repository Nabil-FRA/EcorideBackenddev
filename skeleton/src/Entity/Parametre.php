<?php

namespace App\Entity;

use App\Repository\ParametreRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ParametreRepository::class)]
class Parametre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['parametre:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['parametre:read', 'parametre:write'])]
    private ?string $propriete = null;

    #[ORM\Column(length: 50)]
    #[Groups(['parametre:read', 'parametre:write'])]
    private ?string $valeur = null;

    /**
     * Relation OneToOne avec l'entité `Dispose`.
     * Une `Parametre` peut avoir un seul `Dispose`.
     */
    #[ORM\OneToOne(mappedBy: 'parametre', cascade: ['persist', 'remove'])]
    #[Groups(['parametre:read', 'parametre:write'])]
    private ?Dispose $dispose = null;

    // --------------------
    // GETTERS / SETTERS
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPropriete(): ?string
    {
        return $this->propriete;
    }

    public function setPropriete(string $propriete): self
    {
        $this->propriete = $propriete;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): self
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getDispose(): ?Dispose
    {
        return $this->dispose;
    }

    public function setDispose(?Dispose $dispose): self
    {
        // Vérification de la relation bidirectionnelle
        if ($dispose && $dispose->getParametre() !== $this) {
            $dispose->setParametre($this);
        }

        $this->dispose = $dispose;

        return $this;
    }
}
