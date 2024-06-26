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
        if ($search) {
            $produits = $produitRepository->findByName($search);
        } else {
            $produits = $produitRepository->findAll();
        }
    
        // Filter products with quantity > 0
        $produits = array_filter($produits, function($produit) {
            return $produit->getQuantite() > 0;
        });
    
        return $this->render('vente/index.html.twig', [
            'produits' => $produits,
        ]);
    }
    
    #[Route('/produits/recherche', name: 'produits_recherche', methods: ['GET'])]
    public function rechercherProduits(Request $request, ProduitRepository $produitRepository): JsonResponse
    {
        $search = $request->query->get('search', '');
        $produits = $produitRepository->findByName($search);
    
        $data = [];
        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'name' => $produit->getName(),
                'prix' => $produit->getPrix(),
                'quantite' => $produit->getQuantite(),
                'image' => $produit->getImage(), // Assurez-vous que getImage() retourne le chemin de l'image correct
            ];
        }
    
        return new JsonResponse($data);
    }
    #[Route('/produits/tous', name: 'produits_tous', methods: ['GET'])]

    public function tousProduits(ProduitRepository $produitRepository): JsonResponse
    {
        $produits = $produitRepository->findAll();
    
        $data = [];
        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'name' => $produit->getName(),
                'prix' => $produit->getPrix(),
                'quantite' => $produit->getQuantite(),
                'image' => $produit->getImage(), // Assurez-vous que getImage() retourne le chemin de l'image correct
            ];
        }
    
        return new JsonResponse($data);
    }
    
  

    #[Route('/produits/choisir/{id}', name: 'produit_choisir', methods: ['POST'])]
    public function choisirProduit(Request $request, Produit $produit): Response
    {
        $session = $request->getSession();

        $produitsChoisis = $session->get('produits_choisis', []);

        // Vérification pour mettre à jour la quantité si le produit est déjà sélectionné
        $produitId = $produit->getId();
        if (isset($produitsChoisis[$produitId])) {
            $produitsChoisis[$produitId]['quantite']++;
        } else {
            $produitsChoisis[$produitId] = [
                'produit' => $produit,
                'quantite' => 1,
            ];
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
            $produits[] = [
                'produit' => $produit,
                'quantite' => $quantite,
                'subtotal' => $subtotal,
            ];
        }
    
        // Récupérer les méthodes de paiement
        $paiements = $entityManager->getRepository(Paiement::class)->findAll();
    
        return $this->render('vente/vente.html.twig', [
            'produitsChoisis' => $produits,
            'total' => $total,
            'paiements' => $paiements,
        ]);
    }
    #[Route('/ventes/valider/{total}', name: 'valider_vente', methods: ['POST'])]
    public function validerVente(Request $request, $total, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $produitsChoisis = $session->get('produits_choisis', []);
        $vente = new Vente();
        $vente->setDateVente(new \DateTime());
    
        $paiementId = $request->request->get('paiement');
        $datePaie = new \DateTime($request->request->get('datePaie'));
    
        $paiement = $entityManager->getRepository(Paiement::class)->find($paiementId);
        $vente->setPaiement($paiement);
        $vente->setMontant($total);
        $vente->setUser($this->getUser());
        $entityManager->persist($vente);
    
        foreach ($produitsChoisis as $item) {
            $produit = $item['produit'];
            $quantite = $item['quantite'];
            $product = $entityManager->getRepository(Produit::class)->find($produit);
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
    
        $pdfContent = $this->generateInvoicePdf($entityManager, $vente, $produitsChoisis, $total, $paiement->getMethode(), $montantPaye);
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice.pdf"'
        ]);
    }
    
    private function generateInvoicePdf(EntityManagerInterface $entityManager, Vente $vente, array $produitsChoisis, float $total, string $paymentMethod, float $montantPaye)
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
    
        $dompdf = new Dompdf($options);
    
        $productDetails = $entityManager->getRepository(DetailleVente::class)->findBy(['vente' => $vente]);
    
        $productsForTemplate = [];
        foreach ($productDetails as $detail) {
            $product = $detail->getProduit();
            $productsForTemplate[] = [
                'produit' => $product,
                'quantite' => $detail->getQuantite(),
                'prix' => $detail->getPrixUnutaire(),
                'subtotal' => $detail->getQuantite() * $detail->getPrixUnutaire(),
            ];
        }
    
        $html = $this->renderView('vente/invoice.html.twig', [
            'vente' => $vente,
            'produitsChoisis' => $productsForTemplate,
            'total' => $total,
            'paymentMethod' => $paymentMethod,
            'montantPaye' => $montantPaye,
        ]);
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return $dompdf->output();
    }
      
}