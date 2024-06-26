<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Entity\User;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/admin' )]
class PaiementController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/paiements', name: 'paiements', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render("paiement/index.html.twig");
    }

    #[Route('/paiements/datatables', name: 'paiements_datatables', methods: ['GET'])]
    public function datatables(Request $request, PaiementRepository $paiementRepository): JsonResponse
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

        $queryBuilder = $paiementRepository->createQueryBuilder('p');
        if (!empty($searchValue)) {
            $queryBuilder->where('p.methode LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');
        }

        $filteredQueryBuilder = clone $queryBuilder;
        $totalFilteredRecords = (clone $filteredQueryBuilder)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        $queryBuilder->orderBy('p.' . $orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $paiements = $queryBuilder->getQuery()->getResult();
        $data = [];
        foreach ($paiements as $paiement) {
            $data[] = [
                'id' => $paiement->getId(),
                'methode' => $paiement->getMethode(),
                'user' => $paiement->getUser() ? $paiement->getUser()->getUsername() : 'N/A',
            ];
        }

        $totalRecords = $paiementRepository->count([]);
        return new JsonResponse([
            'draw' => $request->query->getInt('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFilteredRecords,
            'data' => $data,
        ]);
    }

    #[Route('/addpaiement', name: 'paiement_add_form', methods: ['GET', 'POST'])]
    public function addPaiementForm(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $paiement = new Paiement();
            $paiement->setMethode($request->request->get('methode'));
            $user = $this->getUser();
            $paiement->setUser($user);

            $this->entityManager->persist($paiement);
            $this->entityManager->flush();

            return $this->redirectToRoute('paiements');
        }

        return $this->render('paiement/ajouter.html.twig');
    }

    #[Route('/paiements/{id}', name: 'paiement_edit', methods: ['GET', 'POST'])]
    public function editPaiement(Request $request, Paiement $paiement): Response
    {
        if ($request->isMethod('POST')) {
            $paiement->setMethode($request->request->get('methode'));
            $user = $this->entityManager->getRepository(User::class)->find($request->request->get('user'));
            if ($user) {
                $paiement->setUser($user);
            }
            $this->entityManager->flush();

            return $this->redirectToRoute('paiements');
        }

        return $this->render('paiement/edit.html.twig', ['paiement' => $paiement]);
    }

    #[Route('/paiements/get/{id}', name: 'paiement_get', methods: ['GET'])]
    public function getPaiementById(Paiement $paiement): JsonResponse
    {
        return new JsonResponse([
            'id' => $paiement->getId(),
            'methode' => $paiement->getMethode(),
            'user' => $paiement->getUser() ? $paiement->getUser()->getUsername() : null,
        ]);
    }
}
