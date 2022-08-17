<?php

namespace App\Controller;

use App\Class\Cart;
use App\Class\Mailjet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Order;

class OrderCancelController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        return $this->entityManager = $entityManager;
    }

    #[Route('/commande/erreur/{stripeSessionId}', name: 'app_order_cancel')]
    public function index($stripeSessionId, Cart $cart): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);

        if(!$order || $order->getUser() != $this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        foreach ($cart->getFull() as $product) { 
            
            $product['product']->setStock($product['product']->getStock() + $product['quantity']);
        }
    
        // envoyer email d'échec de commande

        $mail = new Mailjet();
        $content = 'Bonjour ' . $order->getUser()->getFirstname() . '<br> Echec de votre commande.<br> <br>';
        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFullname(), 'Un problème est survenu lors de votre commande.', $content);

        return $this->render('order_cancel/index.html.twig', [
            'order'=>$order
        ]);
    }
}
