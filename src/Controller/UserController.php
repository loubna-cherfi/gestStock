<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
#[Route('/admin' )]
class UserController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/usersinfo', name: 'usersinfo', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render("user/index.html.twig");
    }

    #[Route('/users/datatables', name: 'users_datatables', methods: ['GET'])]
    public function datatables(Request $request, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);

        // Récupérer et vérifier le paramètre de recherche
        $search = $request->query->all('search');
        $searchValue = isset($search['value']) ? $search['value'] : '';

        // Récupérer et vérifier les paramètres de tri
        $order = $request->query->all('order');
        $orderColumnIndex = isset($order[0]['column']) ? $order[0]['column'] : 0;
        $orderDir = isset($order[0]['dir']) ? $order[0]['dir'] : 'asc';

        // Récupérer et vérifier les colonnes
        $columns = $request->query->all('columns');
        $orderColumn = isset($columns[$orderColumnIndex]['data']) ? $columns[$orderColumnIndex]['data'] : 'id';

        // Construire la requête principale
        $queryBuilder = $userRepository->createQueryBuilder('u');

        if (!empty($searchValue)) {
            $queryBuilder->where('u.email LIKE :search')
                ->orWhere('u.username LIKE :search')
                ->setParameter('search', '%' . $searchValue . '%');
        }

        // Cloner le queryBuilder pour obtenir le total des enregistrements filtrés
        $filteredQueryBuilder = clone $queryBuilder;
        $totalFilteredRecords = (clone $filteredQueryBuilder)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        // Appliquer l'ordre, la pagination et récupérer les résultats
        $queryBuilder->orderBy('u.' . $orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $users = $queryBuilder->getQuery()->getResult();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'mobile' => $user->getMobile(),
                'adress' => $user->getAdress(),
                'roles' => implode(', ', $user->getRoles()),
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
    #[Route('/adduser', name: 'user_add_form', methods: ['GET'])]
    public function addUserForm(): Response
    {
        return $this->render('user/ajouter.html.twig');
    }

    #[Route('/users/add', name: 'user_add', methods: ['POST'])]
    public function addUser(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Récupérer les données du formulaire
        $email = $request->request->get('email');
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $mobile = $request->request->get('mobile');
        $adress = $request->request->get('adress');
        $role = $request->request->get('role');

        // Vérifier si l'email existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            // Afficher un message d'erreur et rediriger
            $this->addFlash('error', 'L\'adresse e-mail existe déjà.');
            return $this->redirectToRoute('user_add_form');
        }

        // Créer une nouvelle instance de l'entité User
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setMobile($mobile);
        $user->setAdress($adress);

        // Hasher le mot de passe
        $passwordHasherFactory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt']
        ]);
        $passwordHasher = $passwordHasherFactory->getPasswordHasher('common');
        $user->setPassword($passwordHasher->hash($password));

        // Ajouter le rôle
        if ($role === 'admin') {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }

        // Enregistrer l'utilisateur
        $entityManager->persist($user);
        $entityManager->flush();

        // Ajouter un message de succès et rediriger
        $this->addFlash('success', 'Utilisateur ajouté avec succès.');

        return $this->redirectToRoute('users');
    }

    #[Route('/users/{id}', name: 'user_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, User $user): Response
    {
        if ($request->isMethod('POST')) {
            $user->setUsername($request->request->get('username'));
            $user->setEmail($request->request->get('email'));
            $user->setMobile($request->request->get('mobile'));
            $user->setAdress($request->request->get('adress'));
    
            $role = $request->request->get('role');
            if ($role === 'ROLE_ADMIN') {
                $user->setRoles(['ROLE_ADMIN']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }
    
            $password = $request->request->get('password');
            if ($password) {
                $passwordHasherFactory = new PasswordHasherFactory([
                    'common' => ['algorithm' => 'bcrypt']
                ]);
                $passwordHasher = $passwordHasherFactory->getPasswordHasher('common');
                $user->setPassword($passwordHasher->hash($password));
            }
    
            $this->entityManager->flush();
            return $this->redirectToRoute('users');
        }
    
        return $this->render('user/edit.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/users/get/{id}', name: 'user_get', methods: ['GET'])]
    public function getUserById(User $user): JsonResponse
    {
        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'mobile' => $user->getMobile(),
            'adress' => $user->getAdress(),
            'roles' => $user->getRoles(),
        ]);
    }



}