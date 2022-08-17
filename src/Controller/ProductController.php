<?php

namespace App\Controller;

use App\Class\Search;
use App\Entity\Product;
use App\Form\SearchType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        return $this->entityManager = $entityManager;
    }

    #[Route('/boutique', name: 'app_products')]
    public function index(Request $request): Response
    {
        $search = new Search();
        $form = $this->createForm(SearchType::class, $search);

        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $produits = $this->entityManager->getRepository(Product::class)->findWithSearch($search);
        } else {
            $produits = $this->entityManager->getRepository(Product::class)->findByHasStock();
        }

        return $this->render('product/index.html.twig', [
            'products' =>$produits,
            'form' => $form->createView()
        ]);
    }

    #[Route('/produit/{slug}', name: 'app_product')]
    public function show($slug): Response
    {
        $produit = $this->entityManager->getRepository(Product::class)->findOneBySlug($slug);

        if(!$produit){
            return $this->redirectToRoute('app_products');
        } else {
            return $this->render('product/show.html.twig', [
                'product' =>$produit
            ]);
        }
        
    }
}
