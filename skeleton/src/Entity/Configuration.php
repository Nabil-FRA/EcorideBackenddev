<?php

namespace App\Entity;

use App\Repository\ConfigurationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['configuration:read', 'configuration:write'])]
    private ?int $id = null;

    /**
     * @var Collection<int, Dispose>
     */
    #[ORM\OneToMany(targetEntity: Dispose::class, mappedBy: 'configuration', cascade: ['persist', 'remove'])]
    #[Groups(['configuration:read'])]
    private Collection $disposes;

    /**
     * Relation OneToMany avec l'entité ParametreUtilisateur.
     * Une Configuration peut être associée à plusieurs ParametreUtilisateur.
     */
    #[ORM\OneToMany(mappedBy: 'configuration', targetEntity: ParametreUtilisateur::class, cascade: ['persist', 'remove'])]
    #[Groups(['configuration:read'])]
    private Collection $parametreUtilisateur;

    public function __construct()
    {
        $this->disposes = new ArrayCollection();
        $this->parametresUtilisateurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Dispose>
     */
    public function getDisposes(): Collection  // ✅ Correction ici (pluriel)
{
    return $this->disposes;
}

public function addDispose(Dispose $dispose): static
{
    if (!$this->disposes->contains($dispose)) {  // ✅ Correction ici (pluriel)
        $this->disposes->add($dispose);
        $dispose->setConfiguration($this);
    }

    return $this;
}

public function removeDispose(Dispose $dispose): static
{
    if ($this->disposes->removeElement($dispose)) {  // ✅ Correction ici (pluriel)
        if ($dispose->getConfiguration() === $this) {
            $dispose->setConfiguration(null);
        }
    }

    return $this;
}
    /**
     * @return Collection<int, ParametreUtilisateur>
     */
    public function getParametresUtilisateurs(): Collection
    {
        return $this->parametresUtilisateurs;
    }

    public function addParametreUtilisateur(ParametreUtilisateur $parametreUtilisateur): static
    {
        if (!$this->parametresUtilisateurs->contains($parametreUtilisateur)) {
            $this->parametresUtilisateurs->add($parametreUtilisateur);
            $parametreUtilisateur->setConfiguration($this);
        }

        return $this;
    }

    public function removeParametreUtilisateur(ParametreUtilisateur $parametreUtilisateur): static
    {
        if ($this->parametresUtilisateurs->removeElement($parametreUtilisateur)) {
            // set the owning side to null (unless already changed)
            if ($parametreUtilisateur->getConfiguration() === $this) {
                $parametreUtilisateur->setConfiguration(null);
            }
        }

        return $this;
    }
}
