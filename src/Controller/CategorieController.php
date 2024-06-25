<?php

namespace App\Controller;
use App\Entity\Categorie;
use App\Entity\User;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class CategorieController extends AbstractController
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
   
    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository): Response
    {
        $categories = $categorieRepository->findAll(); // Fetch categories from repository
    
        return $this->render("categorie/index.html.twig", [
            'categories' => $categories, // Pass categories to the Twig template
        ]);
    }
    #[Route('/categories/datatables', name: 'categories_datatables', methods: ['GET'])]
    public function datatables(Request $request, EntityManagerInterface $em, CategorieRepository $userRepository): JsonResponse
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

        $queryBuilder = $userRepository->createQueryBuilder('u');
        if (!empty($searchValue)) {
            $queryBuilder->where('u.name LIKE :search')
                ->orWhere('u.name LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');
        }
        $filteredQueryBuilder = clone $queryBuilder;
        $totalFilteredRecords = (clone $filteredQueryBuilder)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $queryBuilder->orderBy('u.' . $orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);
        $categories = $queryBuilder->getQuery()->getResult();
        $data = [];
        foreach ($categories as $categorie) {
            $data[] = [
                'id' => $categorie->getId(),
                'name' => $categorie->getName(),   
            ];
        }
        $totalRecords = $userRepository->count([]);
        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFilteredRecords,
            'data' => $data,
        ]);
    }
    #[Route('/addcategorie', name: 'categorie_add_form', methods: ['GET', 'POST'])]
    public function addcategorieForm(Request $request, CategorieRepository $categorieRepository): Response
    {
        if ($request->isMethod('POST')) {
            $categorie = new Categorie();
            $categorie->setName($request->request->get('name'));
            $user = $this->getUser();
            $categorie->setIdUser($user);
            $this->entityManager->persist($categorie);
            $this->entityManager->flush();
            return $this->redirectToRoute('categories');
        }
        return $this->render('categorie/ajouter.html.twig' );
    }
    
    #[Route('/categories/{id}', name: 'categorie_edit', methods: ['GET', 'POST'])]
    public function editCategorie(Request $request, Categorie $categorie): Response
    {
        if ($request->isMethod('POST')) {
            $categorie->setName($request->request->get('name'));
            $user = $this->entityManager->getRepository(User::class)->find($request->request->get('user'));
            if ($user) {
                $categorie->setIdUser($user); 
            }
            $this->entityManager->flush();
            return $this->redirectToRoute('categories');
        }
        return $this->render('categorie/edit.html.twig', [
            'categorie' => $categorie,
        ]);
    }
    
    #[Route('/categories/get/{id}', name: 'categorie_get', methods: ['GET'])]
    public function getCategorieById(Categorie $categorie): JsonResponse
    {
      return   new JsonResponse([
            'id' => $categorie->getId(),
            'name' => $categorie->getName(),
            'user' => $categorie->getIdUser() ? $categorie->getIdUser()->getUsername() : null,
        ]);
}
}