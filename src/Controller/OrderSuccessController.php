<?php

namespace App\Controller;

use App\Class\Cart;
use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Class\Mailjet;

class OrderSuccessController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/commande/merci/{stripeSessionId}', name: 'app_order_success')]
    public function index($stripeSessionId, Cart $cart): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);

        if (!$order || $order->getUser() != $this->getUser()) {
            return $this->redirectToRoute('app_home');
        }


        // modifier le state à 1
        if ($order->getState() == 0) {

            foreach($cart->getFull() as $product){
                $product['product']->setStock($product['product']->getStock() - $product['quantity']);
            }

            //vider la session "cart"
            $cart->remove();

            $order->setState(1);
            $this->entityManager->flush();

            // envoyer email de confirmation de commande
            $mail = new Mailjet();
            $content = 'Bonjour ' . $order->getUser()->getFirstname() . '<br> Merci pour votre commande.<br> <br>';
            $mail->send($order->getUser()->getEmail(), $order->getUser()->getFullname(), 'Votre commande est bien validée.', $content);
        }

        return $this->render('order_success/index.html.twig', [
            'order' => $order
        ]);
    }
}
