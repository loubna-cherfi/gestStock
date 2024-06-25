<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $prix = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable:true)]
    private ?\DateTimeInterface $dateEntreer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE,nullable:true)]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    private ?User $user = null;

    /**
     * @var Collection<int, DetailleVente>
     */
    #[ORM\OneToMany(targetEntity: DetailleVente::class, mappedBy: 'produit',cascade:["persist"])]
    private Collection $detailleVentes;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(length: 255,nullable:true)]
    private ?string $image =null;

    public function __construct()
    {
        $this->detailleVentes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDateEntreer(): ?\DateTimeInterface
    {
        return $this->dateEntreer;
    }

    public function setDateEntreer(\DateTimeInterface $dateEntreer): static
    {
        $this->dateEntreer = $dateEntreer;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTimeInterface $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

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
     * @return Collection<int, DetailleVente>
     */
    public function getDetailleVentes(): Collection
    {
        return $this->detailleVentes;
    }

    public function addDetailleVente(DetailleVente $detailleVente): static
    {
        if (!$this->detailleVentes->contains($detailleVente)) {
            $this->detailleVentes->add($detailleVente);
            $detailleVente->setProduit($this);
        }

        return $this;
    }

    public function removeDetailleVente(DetailleVente $detailleVente): static
    {
        if ($this->detailleVentes->removeElement($detailleVente)) {
            // set the owning side to null (unless already changed)
            if ($detailleVente->getProduit() === $this) {
                $detailleVente->setProduit(null);
            }
        }

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
