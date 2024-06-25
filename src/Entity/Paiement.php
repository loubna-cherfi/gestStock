<?php

namespace App\Entity;
use App\Entity\User;

use App\Repository\PaiementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $methode = null;

    #[ORM\ManyToOne(inversedBy: 'paiements')]
    private ?User $user = null;

    /**
     * @var Collection<int, Vente>
     */
    #[ORM\OneToMany(targetEntity: Vente::class, mappedBy: 'paiement')]
    private Collection $ventes;

    /**
     * @var Collection<int, Paiementuser>
     */
    #[ORM\OneToMany(targetEntity: Paiementuser::class, mappedBy: 'paiement')]
    private Collection $paiementusers;



    public function __construct()
    {
        $this->ventes = new ArrayCollection();
        $this->paiementusers = new ArrayCollection();
    }

    #[ORM\ManyToOne(inversedBy: 'paiement')]
   
    // Add the getId method here
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethode(): ?string
    {
        return $this->methode;
    }

    public function setMethode(string $methode): static
    {
        $this->methode = $methode;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Vente>
     */
    public function getVentes(): Collection
    {
        return $this->ventes;
    }

    public function addVente(Vente $vente): static
    {
        if (!$this->ventes->contains($vente)) {
            $this->ventes->add($vente);
            $vente->setPaiement($this);
        }

        return $this;
    }

    public function removeVente(Vente $vente): static
    {
        if ($this->ventes->removeElement($vente)) {
            // set the owning side to null (unless already changed)
            if ($vente->getPaiement() === $this) {
                $vente->setPaiement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Paiementuser>
     */
    public function getPaiementusers(): Collection
    {
        return $this->paiementusers;
    }

    public function addPaiementuser(Paiementuser $paiementuser): static
    {
        if (!$this->paiementusers->contains($paiementuser)) {
            $this->paiementusers->add($paiementuser);
            $paiementuser->setPaiement($this);
        }

        return $this;
    }

    public function removePaiementuser(Paiementuser $paiementuser): static
    {
        if ($this->paiementusers->removeElement($paiementuser)) {
            // set the owning side to null (unless already changed)
            if ($paiementuser->getPaiement() === $this) {
                $paiementuser->setPaiement(null);
            }
        }

        return $this;
    }



  
}
