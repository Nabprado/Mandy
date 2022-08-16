<?php

namespace App\Controller;

use App\Class\Mailjet;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/nous-contacter', name: 'app_contact')]
    public function index(Request $request): Response
    {

        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            //API zenDesk??
            $name = $form->get('nom')->getData().' '.$form->get('prenom')->getData();
            $email = $form->get('email')->getData();
            $message = $form->get('message')->getData();
            $content = "<< De la part de : ".$name. ". <br\> Email :".$email." <br> Message : ".$message." >>";
            
            
            $mail = new Mailjet();
            $mail->send('nab.prado@outlook.Fr', 'Mandy', 'Nouvelle demande de contact', $content);
            $this->addFlash('notice', 'Merci de nous avoir contacté. Notre équipe va vous répondre dans les meilleurs délais.');
            
        }
        return $this->render('contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
