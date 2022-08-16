<?php

namespace App\Controller;

use App\Class\Cart;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    #[Route('/commande/create-session/{reference}', name: 'app_stripe_create_session')]
    public function index(EntityManagerInterface $entityManager, Cart $cart, $reference): Response
    {
        $products_for_stripe = [];
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';

        $order = $entityManager->getRepository(Order::class)->findOneBy(['reference'=>$reference]);

        if(!$order){
            $this->redirectToRoute('app_order');
        }

        foreach($order->getOrderDetails()->getValues() as $product){
            // $product_object = $entityManager->getRepository(Product::class)->findOneByName($product->getProduct());
            $products_for_stripe[] = [
                'price_data' => [
                    'currency'=>'EUR',
                    'unit_amount'=>$product->getPrice(),
                    'product_data'=> [
                        'name'=> $product->getProduct()
                    ],
                ],
                'quantity'=> $product->getQuantity()
            ];
        }

        $products_for_stripe[] = [
            'price_data' => [
                'currency'=>'EUR',
                'unit_amount'=>$order->getCarrierPrice(),
                'product_data'=> [
                    'name'=> $order->getCarrierName()
                ],
            ],
            'quantity'=> 1
        ];

        Stripe::setApiKey('sk_test_51LMtVWBldxthWhxIDMPeDG48u3NFJzFS5dnD2kD3pW4AFZos6T0hmFhWprqXHgkFWk0kM0OAHN7QmuyCRPaW7tro00OCrdDuAx');
        Stripe::setApiVersion('2020-08-27');

        $checkout_session = Session::create([
            'customer_email'=>$this->getUser()->getEmail(),
            'line_items'=> [
                $products_for_stripe
            ],
            'payment_method_types'=> [
                'card'
            ],
            'mode'=>'payment',
            'success_url'=> $YOUR_DOMAIN.'/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url'=> $YOUR_DOMAIN.'/commande/erreur/{CHECKOUT_SESSION_ID}'
        ]);

        $order->setStripeSessionId($checkout_session->id);
        $entityManager->flush();

        return $this->redirect($checkout_session->url, 303);
    }
}
