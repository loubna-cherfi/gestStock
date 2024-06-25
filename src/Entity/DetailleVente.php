<?php

namespace App\Entity;

use App\Repository\DetailleVenteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailleVenteRepository::class)]
class DetailleVente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $prixUnutaire = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\ManyToOne(inversedBy: 'detailleVentes')]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(inversedBy: 'detailleVentes')]
    private ?Vente $vente = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrixUnutaire(): ?float
    {
        return $this->prixUnutaire;
    }

    public function setPrixUnutaire(float $prixUnutaire): static
    {
        $this->prixUnutaire = $prixUnutaire;

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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): static
    {
        $this->vente = $vente;

        return $this;
    }
}
