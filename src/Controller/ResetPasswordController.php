<?php

namespace App\Controller;

use App\Class\Mailjet;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/mot-de-passe-oublié', name: 'app_reset_password')]
    public function index(Request $request): Response
    {

        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        if($request->get('email')){
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));

            if($user) {
                //étape 1 : enregistrer en BDD la demande de reset de password avec user, token, createdAt
                $reset_password = new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new DateTimeImmutable());
                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                //étape 2 : envoyer email à l'utilisateur avec un lien lui permettant de mettre à jour son mot de passe
                $url = $this->generateUrl('app_update_password', [
                    'token' => $reset_password->getToken()
                ]);

                $content = "Bonjour ".$user->getFirstname().", <br/> Vous avez demandé à réinitialiser votre mot de passe sur Mandy. <br> <br>";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href='".$url."'> mettre à jour votre mot de passe</a>.";

                $mail = new Mailjet();
                $mail->send($user->getEmail(), $user->getFirstname().' '.$user->getLastname(), 'Réinitialiser votre mot de passe sur Mandy', $content);

                $this->addFlash('notice', 'Vous allez recevoir dans quelques secondes un mail avec la procédure pour réinitialiser votre mot de passe.');

            } else {
                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        }
        return $this->render('reset_password/index.html.twig');
    }

    #[Route('/modifier-mon-mot-de-passe/{token}', name: 'app_update_password')]
    public function update(Request $request, $token, UserPasswordHasherInterface $hasher): Response
    {
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);
        if(!$reset_password){
            return $this->redirectToRoute('app_reset_password');
        } else {
            //vérifier si createdAt = now - 3 heures
            $now = new DateTimeImmutable();
            if($now > $reset_password->getCreatedAt()->modify('+ 3 hour')) {
                $this->addFlash('notice', 'Votre demande de changement de mot de passe a expiré. Merci de la renouveler.');
                return $this->redirectToRoute('app_reset_password');
            }

            $form = $this->createForm(ResetPasswordType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()){
                $new_pwd = $form->get('new_password')->getData();

                $password = $hasher->hashPassword($reset_password->getUser(), $new_pwd);
                $reset_password->getUser()->setPassword($password);

                $this->entityManager->flush();

                $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');
                return $this->redirectToRoute('app_login');

            }

            return $this->render('reset_password/update.html.twig', [
                'form' => $form->createView()
            ]);

            
        }
    }
}
