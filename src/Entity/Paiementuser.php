<?php

namespace App\Entity;

use App\Repository\PaiementuserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementuserRepository::class)]
class Paiementuser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paiementusers')]
    private ?Paiement $paiement = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Vente $vente = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datePaie = null;

    #[ORM\Column(nullable:true)]
    private ?int $numcartbancaire = null;

    #[ORM\Column(type: Types::DATE_MUTABLE,nullable:true)]
    private ?\DateTimeInterface $date_experation = null;

    #[ORM\Column (nullable:true)]
    private ?int $cache = null;


    #[ORM\Column(type: Types::DATE_MUTABLE)]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaiement(): ?Paiement
    {
        return $this->paiement;
    }

    public function setPaiement(?Paiement $paiement): static
    {
        $this->paiement = $paiement;

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

    public function getDatePaie(): ?\DateTimeInterface
    {
        return $this->datePaie;
    }

    public function setDatePaie(\DateTimeInterface $datePaie): static
    {
        $this->datePaie = $datePaie;

        return $this;
    }

    public function getNumcartbancaire(): ?int
    {
        return $this->numcartbancaire;
    }

    public function setNumcartbancaire(int $numcartbancaire): static
    {
        $this->numcartbancaire = $numcartbancaire;

        return $this;
    }

    public function getDateExperation(): ?\DateTimeInterface
    {
        return $this->date_experation;
    }

    public function setDateExperation(\DateTimeInterface $date_experation): static
    {
        $this->date_experation = $date_experation;

        return $this;
    }

    public function getCache(): ?int
    {
        return $this->cache;
    }

    public function setCache(int $cache): static
    {
        $this->cache = $cache;

        return $this;
    }

}
