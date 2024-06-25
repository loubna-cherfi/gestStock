<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ChangepasswordController extends AbstractController
{
    #[Route('/changepassword', name: 'app_changepassword')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You need to be logged in to change your password.');
        }

        $newPassword = $request->request->get('new-password');
        $confirmPassword = $request->request->get('confirm-password');

        if ($newPassword === $confirmPassword) {
            $encodedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($encodedPassword);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été changé avec succès.');
        } else {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
        }

        return $this->redirectToRoute('app_test'); // Changez 'homepage' par votre route d'accueil
    }
}
