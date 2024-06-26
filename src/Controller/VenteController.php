<?php

namespace App\Controller;

use App\Entity\DetailleVente;
use App\Entity\Paiement;
use App\Entity\Paiementuser;
use App\Entity\Produit;
use App\Entity\Vente;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

class VenteController extends AbstractController
{
    #[Route('/Ventecard', name: 'ventes_cards', methods: ['GET'])]
    public function productsCards(Request $request, ProduitRepository $produitRepository): Response
    {
        $search = $request->query->get('search', '');
        $produits = $search ? $produitRepository->findByName($search) : $produitRepository->findAll();

        // Filter products with quantity > 0
        $produits = array_filter($produits, fn($produit) => $produit->getQuantite() > 0);

        return $this->render('vente/index.html.twig', ['produits' => $produits]);
    }

    #[Route('/produits/recherche', name: 'produits_recherche', methods: ['GET'])]
    public function rechercherProduits(Request $request, ProduitRepository $produitRepository): JsonResponse
    {
        $search = $request->query->get('search', '');
        $produits = $produitRepository->findByName($search);

        $data = array_map(fn($produit) => [
            'id' => $produit->getId(),
            'name' => $produit->getName(),
            'prix' => $produit->getPrix(),
            'quantite' => $produit->getQuantite(),
            'image' => $produit->getImage(), // Ensure getImage() returns the correct image path
        ], $produits);

        return new JsonResponse($data);
    }

    #[Route('/produits/tous', name: 'produits_tous', methods: ['GET'])]
    public function tousProduits(ProduitRepository $produitRepository): JsonResponse
    {
        $produits = $produitRepository->findAll();

        $data = array_map(fn($produit) => [
            'id' => $produit->getId(),
            'name' => $produit->getName(),
            'prix' => $produit->getPrix(),
            'quantite' => $produit->getQuantite(),
            'image' => $produit->getImage(), // Ensure getImage() returns the correct image path
        ], $produits);

        return new JsonResponse($data);
    }

    #[Route('/produits/choisir/{id}', name: 'produit_choisir', methods: ['POST'])]
    public function choisirProduit(Request $request, Produit $produit): Response
    {
        $session = $request->getSession();
        $produitsChoisis = $session->get('produits_choisis', []);
        $produitId = $produit->getId();

        if (isset($produitsChoisis[$produitId])) {
            $produitsChoisis[$produitId]['quantite']++;
        } else {
            $produitsChoisis[$produitId] = ['produit' => $produit, 'quantite' => 1];
        }

        $session->set('produits_choisis', $produitsChoisis);

        return $this->redirectToRoute('ventes_cards');
    }

    #[Route('/ventes', name: 'ventes', methods: ['GET'])]
    public function ventes(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $produitsChoisis = $session->get('produits_choisis', []);
        $produits = [];
        $total = 0;

        foreach ($produitsChoisis as $item) {
            $produit = $item['produit'];
            $quantite = $item['quantite'];
            $subtotal = $produit->getPrix() * $quantite;
            $total += $subtotal;
            $produits[] = ['produit' => $produit, 'quantite' => $quantite, 'subtotal' => $subtotal];
        }

        $paiements = $entityManager->getRepository(Paiement::class)->findAll();

        return $this->render('vente/vente.html.twig', [
            'produitsChoisis' => $produits,
            'total' => $total,
            'paiements' => $paiements,
        ]);
    }

  // src/Controller/VenteController.php

#[Route('/ventes/valider/{total}', name: 'valider_vente', methods: ['POST'])]
public function validerVente(Request $request, $total, EntityManagerInterface $entityManager): Response
{
    $session = $request->getSession();
    $produitsChoisis = $session->get('produits_choisis', []);

    $vente = new Vente();
    $vente->setDateVente(new \DateTime());
    $vente->setMontant($total);
    $vente->setUser($this->getUser());

    $paiementId = $request->request->get('paiement');
    $datePaie = new \DateTime($request->request->get('datePaie'));
    $paiement = $entityManager->getRepository(Paiement::class)->find($paiementId);

    $vente->setPaiement($paiement);
    $entityManager->persist($vente);

    foreach ($produitsChoisis as $item) {
        $produit = $item['produit'];
        $quantite = $item['quantite'];
        $product = $entityManager->getRepository(Produit::class)->find($produit->getId());
        $product->setQuantite($product->getQuantite() - $quantite);
        $entityManager->persist($product);

        $detailleVente = new DetailleVente();
        $detailleVente->setPrixUnutaire($produit->getPrix());
        $detailleVente->setQuantite($quantite);
        $detailleVente->setProduit($product);
        $detailleVente->setVente($vente);
        $entityManager->persist($detailleVente);
    }

    $entityManager->flush();

    $paiementUser = new Paiementuser();
    $paiementUser->setPaiement($paiement);
    $paiementUser->setVente($vente);
    $paiementUser->setDatePaie($datePaie);

    $montantPaye = 0;
    if ($paiement->getMethode() === 'Espece') {
        $cache = $request->request->get('cache');
        $paiementUser->setCache($cache);
        $montantPaye = $cache;
    } elseif ($paiement->getMethode() === 'Carte Bancaire') {
        $numCarteBancaire = $request->request->get('numcartbancaire');
        $dateExpiration = $request->request->get('date_experation');
        $montantCarte = $request->request->get('cache_card');

        if ($numCarteBancaire && $dateExpiration) {
            $paiementUser->setNumcartbancaire($numCarteBancaire);
            $paiementUser->setDateExperation(new \DateTime($dateExpiration));
            $paiementUser->setCache($montantCarte);
            $montantPaye = $montantCarte;
        } else {
            return new Response('Les champs de la carte bancaire sont requis.', 400);
        }
    }

    $entityManager->persist($paiementUser);
    $entityManager->flush();

    $session->remove('produits_choisis');

    return $this->redirectToRoute('download_invoice', [
        'vente' => $vente->getId(),
        'montantPaye' => $montantPaye,
    ]);
}

    #[Route('/invoice/{vente}', name: 'download_invoice', methods: ['GET'])]
    public function downloadInvoice(Vente $vente, EntityManagerInterface $entityManager, Request $request): Response
    {
    
        $productDetails = $entityManager->getRepository(DetailleVente::class)->findBy(['vente' => $vente]);
    
        $total = 0;
        $productsForTemplate = [];
        $montantPaye=$request->query->get('montantPaye');
        foreach ($productDetails as $item) {
            $product = $item->getProduit();
            $quantity = $item->getQuantite();
            $subtotal = $product->getPrix() * $quantity;
            $total += $subtotal;
            $productsForTemplate[] = [
                'name' => $product->getName(),
                'price' => $product->getPrix(),
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'paymentMethod' => $vente->getPaiement()->getMethode(),
            ];
        }
    
        $user = $this->getUser();
        $dateVente = $vente->getDateVente();
        $paymentMethod = $vente->getPaiement()->getMethode();
        $montantPaye = $vente->getMontant(); 
    
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
    
        $html = $this->renderView('vente/invoice.html.twig', [
            'vente' => $vente,
            'user' => $user,
            'products' => $productsForTemplate,
            'total' => $total,
            'date' => $dateVente,
            'paymentMethod' => $paymentMethod,
            'montantPaye' => $montantPaye 
        ]);
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice.pdf"',
        ]);
    }
        
}
