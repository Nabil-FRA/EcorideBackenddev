<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['utilisateur:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private $photo = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private ?string $apiToken = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private bool $isChauffeur = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['utilisateur:read', 'utilisateur:write'])]
    private bool $isPassager = false;

    // Relations
    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Participe::class, cascade: ['persist', 'remove'])]
    #[Groups(['utilisateur:read'])]
    private Collection $participes;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Depose::class, cascade: ['persist', 'remove'])]
    #[Groups(['utilisateur:read'])]
    private Collection $deposes;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Gere::class, cascade: ['persist', 'remove'])]
    #[Groups(['utilisateur:read'])]
    private Collection $geres;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: ParametreUtilisateur::class, cascade: ['persist', 'remove'])]
    #[Groups(['utilisateur:read'])]
    private Collection $parametresUtilisateurs;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Possede::class, cascade: ['persist', 'remove'])]
    #[Groups(['utilisateur:read'])]
    private Collection $possedes;

    #[ORM\Column(type: 'integer')]
    private int $credits = 20; // Valeur par défaut à 20

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $suspendedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $suspendReason = null;


    public function __construct()
    {
        $this->participes = new ArrayCollection();
        $this->deposes = new ArrayCollection();
        $this->geres = new ArrayCollection();
        $this->parametresUtilisateurs = new ArrayCollection();
        $this->possedes = new ArrayCollection();
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

    public function setPhoto($photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): self
    {
        $this->pseudo = $pseudo;
        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Suppression des données sensibles
    }
    public function isChauffeur(): bool
    {
        return $this->isChauffeur;
    }
    
    public function setIsChauffeur(bool $isChauffeur): self
    {
        $this->isChauffeur = $isChauffeur;
        return $this;
    }
    
    public function isPassager(): bool
    {
        return $this->isPassager;
    }
    
    public function setIsPassager(bool $isPassager): self
    {
        $this->isPassager = $isPassager;
        return $this;
    }
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->possedes as $possede) {
            $roles[] = $possede->getRole()->getLibelle();
        }
        return array_unique($roles);
    }

   // Relations avec ParametreUtilisateur
public function getParametresUtilisateurs(): Collection
{
    return $this->parametresUtilisateurs; // ✅ Correction ici (ajout du "s")
}

public function addParametreUtilisateur(ParametreUtilisateur $parametreUtilisateur): self
{
    if (!$this->parametresUtilisateurs->contains($parametreUtilisateur)) { // ✅ Correction ici (ajout du "s")
        $this->parametresUtilisateurs->add($parametreUtilisateur);
        $parametreUtilisateur->setUtilisateur($this);
    }
    return $this;
}

public function removeParametreUtilisateur(ParametreUtilisateur $parametreUtilisateur): self
{
    if ($this->parametresUtilisateurs->removeElement($parametreUtilisateur)) { // ✅ Correction ici (ajout du "s")
        if ($parametreUtilisateur->getUtilisateur() === $this) {
            $parametreUtilisateur->setUtilisateur(null);
        }
    }
    return $this;
}

    // Relations avec Possede
    public function getPossedes(): Collection
    {
        return $this->possedes;
    }

    public function addPossede(Possede $possede): self
    {
        if (!$this->possedes->contains($possede)) {
            $this->possedes->add($possede);
            $possede->setUtilisateur($this);
        }
        return $this;
    }

    public function removePossede(Possede $possede): self
    {
        if ($this->possedes->removeElement($possede)) {
            if ($possede->getUtilisateur() === $this) {
                $possede->setUtilisateur(null);
            }
        }
        return $this;
    }
    public function getDeposes(): Collection  // ✅ Correction ici (pluriel)
    {
        return $this->deposes;
    }
    
    public function addDepose(Depose $depose): self
    {
        if (!$this->deposes->contains($depose)) {  // ✅ Correction ici (pluriel)
            $this->deposes->add($depose);
            $depose->setUtilisateur($this);
        }
        return $this;
    }
    
    public function removeDepose(Depose $depose): self
    {
        if ($this->deposes->removeElement($depose)) {  // ✅ Correction ici (pluriel)
            if ($depose->getUtilisateur() === $this) {
                $depose->setUtilisateur(null);
            }
        }
        return $this;
    }

    public function getCredits(): int
{
    return $this->credits;
}

public function setCredits(int $credits): self
{
    $this->credits = $credits;
    return $this;
}

public function isActive(): bool
    {
        return $this->isActive;
    }

public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSuspendedAt(): ?\DateTime
    {
        return $this->suspendedAt;
    }

    public function setSuspendedAt(?\DateTime $suspendedAt): self
    {
        $this->suspendedAt = $suspendedAt;
        return $this;
    }

    public function getSuspendReason(): ?string
    {
        return $this->suspendReason;
    }

    public function setSuspendReason(?string $suspendReason): self
    {
        $this->suspendReason = $suspendReason;
        return $this;
    }

}
