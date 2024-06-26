<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Produit;
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
#[Route('/admin' )]
class ProduitController extends AbstractController
{
    private $entityManager;
    private $slugger;

    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
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
            $produit->setName($request->request->get('name'));
            $produit->setPrix($request->request->get('prix'));
            $produit->setQuantite($request->request->get('quantite'));
    
            // Gestion de l'image
            $file = $request->files->get('image', '');
            if($file){
                
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
            $produit ->setImage($fileName);
            $file->move(
                $this->getParameter('pictures_directory'),
                $fileName
            );
            }

            // if ($imageFile) {
            //     $imageFile->move(
            //         $this->getParameter('pictures_directory'),$imageFile
            //     );
            //     $produit->setImage($imageFile);


                // $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // $safeFilename = $slugger->slug($originalFilename);

                // $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                // dd($safeFilename);
    
                // Déplacement du fichier vers le répertoire où les images sont stockées
                // try {
                //     $imageFile->move(
                //         $this->getParameter('pictures_directory'),
                //         $newFilename
                //     );
                // } catch (FileException $e) {
                //     // Gestion de l'exception si le fichier ne peut pas être déplacé
                //     $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                //     return $this->redirectToRoute('produit_add_form');
                // }
                // $produit->setImage($newFilename); // Sauvegarder le nom du fichier dans l'entité
            // }
    
            $dateEntreer = new \DateTime($request->request->get('dateEntreer'));
            $produit->setDateEntreer($dateEntreer);
    
            $dateExpiration = $request->request->get('dateExpiration');
            if ($dateExpiration != null) {
                $dateExpirationObj = new \DateTime($dateExpiration);
                $produit->setDateExpiration($dateExpirationObj);
            }
    
            $categorieId = $request->request->get('categorie');
            $categorie = $categorieRepository->find($categorieId);
            $produit->setCategorie($categorie);
    
            $user = $this->getUser();
            $produit->setUser($user);
    
            $this->entityManager->persist($produit);
            $this->entityManager->flush();
    
            return $this->redirectToRoute('produits');
        }
    
        $categories = $categorieRepository->findAll();
        return $this->render('produit/ajouter.html.twig', [
            'categories' => $categories,
        ]);
    } 
    #[Route('/produits/{id}', name: 'produit_edit', methods: ['GET', 'POST'])]
    public function editProduit(Request $request, Produit $produit, CategorieRepository $categorieRepository): Response
    {
        if ($request->isMethod('POST')) {
            $produit->setName($request->request->get('name'));
            $produit->setPrix($request->request->get('prix'));
            $produit->setQuantite($request->request->get('quantite'));

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('produit_edit', ['id' => $produit->getId()]);
                }

                $produit->setImage($newFilename);
            }

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

            $categorieId = $request->request->get('categorie');
            $categorie = $categorieRepository->find($categorieId);
            if ($categorie) {
                $produit->setCategorie($categorie);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Le produit a été mis à jour avec succès.');
            return $this->redirectToRoute('produits');
        }

        $categories = $categorieRepository->findAll();
        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'categories' => $categories,
        ]);
    }
    #[Route('/produits/get/{id}', name: 'produit_get', methods: ['GET'])]
public function getProduit(int $id, ProduitRepository $produitRepository): JsonResponse
{
    $produit = $produitRepository->find($id);

    if (!$produit) {
        return new JsonResponse(['error' => 'Produit not found'], 404);
    }

    $data = [
        'id' => $produit->getId(),
        'name' => $produit->getName(),
        'prix' => $produit->getPrix(),
        'dateEntreer' => $produit->getDateEntreer()->format('Y-m-d'),
        'dateExpiration' => $produit->getDateExpiration() ? $produit->getDateExpiration()->format('Y-m-d') : null,
        'categorie' => $produit->getCategorie()->getId(),
        'quantite' => $produit->getQuantite(),
        'image' => $produit->getImage(),
    ];

    return new JsonResponse($data);
}
}
