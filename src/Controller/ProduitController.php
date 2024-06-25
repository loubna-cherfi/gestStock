<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\User;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProduitController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/produits', name: 'produits', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll();
        $produits = $produitRepository->findAll(); // ou toute autre logique de récupération des produits
    
        return $this->render("produit/index.html.twig", [
            'produits' => $produits,
            'categories' => $categories,
        ]);
    }
    #[Route('/produits/datatables', name: 'produits_datatables', methods: ['GET'])]
    public function datatables(Request $request, ProduitRepository $produitRepository): JsonResponse
    {
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);

        $search = $request->query->all('search');
        $searchValue = isset($search['value']) ? $search['value'] : '';

        $order = $request->query->all('order');
        $orderColumnIndex = isset($order[0]['column']) ? $order[0]['column'] : 0;
        $orderDir = isset($order[0]['dir']) ? $order[0]['dir'] : 'asc';

        $columns = $request->query->all('columns');
        $orderColumn = isset($columns[$orderColumnIndex]['data']) ? $columns[$orderColumnIndex]['data'] : 'id';

        $queryBuilder = $produitRepository->createQueryBuilder('p');

        if (!empty($searchValue)) {
            $queryBuilder->where('p.name LIKE :search')
                ->orWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');
        }

        $filteredQueryBuilder = clone $queryBuilder;
        $totalFilteredRecords = (clone $filteredQueryBuilder)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $queryBuilder->orderBy('p.' . $orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $produits = $queryBuilder->getQuery()->getResult();

        $data = [];
        foreach ($produits as $produit) {
            $data[] = [
                'id' => $produit->getId(),
                'name' => $produit->getName(),
                'prix' => $produit->getPrix(),
                'quantite' => $produit->getQuantite(),
                'dateEntreer' => $produit->getDateEntreer()->format('Y-m-d H:i:s'),
                'dateExpiration' => $produit->getDateExpiration() ? $produit->getDateExpiration()->format('Y-m-d H:i:s') : null,
                'categorie' => $produit->getCategorie() ? $produit->getCategorie()->getName() : null,
                'user' => $produit->getUser() ? $produit->getUser()->getUsername() : null,
            ];
        }

        $totalRecords = $produitRepository->count([]);

        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFilteredRecords,
            'data' => $data,
        ]);
    }

    #[Route('/addproduit', name: 'produit_add_form', methods: ['GET', 'POST'])]
    public function addProduitForm(Request $request, CategorieRepository $categorieRepository, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            $produit = new Produit();
            // Récupération des autres champs
            $produit->setName($request->request->get('name'));
            $produit->setPrix($request->request->get('prix'));
            $produit->setQuantite($request->request->get('quantite'));
    
            // Gestion de l'image
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
    
                // Move the file to the directory where images are stored
                try {
                    $imageFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception if file cannot be moved
                    $this->addFlash('error', 'An error occurred while uploading the image.');
                    return $this->redirectToRoute('produit_add_form');
                }
    
                $produit->setImage($newFilename); // Sauvegarder le nom du fichier dans l'entité
            }
    
            // Enregistrement du produit
            $this->entityManager->persist($produit);
            $this->entityManager->flush();
    
            return $this->redirectToRoute('produits');
        }
    
        // Rendu du formulaire
        $categories = $categorieRepository->findAll();
        return $this->render('produit/ajouter.html.twig', [
            'categories' => $categories,
        ]);
    }
    #[Route('/produits/{id}', name: 'produit_edit', methods: ['GET', 'POST'])]
    public function editProduit(Request $request, Produit $produit): Response
    {
        if ($request->isMethod('POST')) {
            $produit->setName($request->request->get('name'));
            $produit->setPrix($request->request->get('prix'));
            $produit->setQuantite($request->request->get('quantite'));
            $produit->setImage($request->request->get('image'));
            $produit->setDateEntreer(new \DateTime($request->request->get('dateEntreer')));

            $dateExpiration = $request->request->get('dateExpiration');
            if ($dateExpiration != null) {
                $dateExpirationObj = new \DateTime($dateExpiration);
                $currentDate = new \DateTime();
                if ($dateExpirationObj <= $currentDate) {
                    $this->addFlash('error', 'La date d\'expiration doit être supérieure à la date actuelle.');
                    return $this->redirectToRoute('produit_edit', ['id' => $produit->getId()]);
                }
                $produit->setDateExpiration($dateExpirationObj);
            }

            $categorie = $request->request->get('categorie');
            if ($categorie) {
                $categorieEntity = $this->entityManager->getRepository(Categorie::class)->find($categorie);
                $produit->setCategorie($categorieEntity);
            }

            $user = $request->request->get('user');
            if ($user) {
                $userEntity = $this->entityManager->getRepository(User::class)->find($user);
                $produit->setUser($userEntity);
            }

            $this->entityManager->flush();
            return $this->redirectToRoute('produits');
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/produits/get/{id}', name: 'produit_get', methods: ['GET'])]
    public function getProduitById(Produit $produit): JsonResponse
    {
        return new JsonResponse([
            'id' => $produit->getId(),
            'name' => $produit->getName(),
            'prix' => $produit->getPrix(),
            'quantite' => $produit->getQuantite(),
            'image' => $produit->getImage(),
            'dateEntreer' => $produit->getDateEntreer()->format('Y-m-d H:i:s'),
            'dateExpiration' => $produit->getDateExpiration() ? $produit->getDateExpiration()->format('Y-m-d H:i:s') : null,
            'categorie' => $produit->getCategorie() ? $produit->getCategorie()->getName() : null,
            'user' => $produit->getUser() ? $produit->getUser()->getUsername() : null,
        ]);
    }
}
