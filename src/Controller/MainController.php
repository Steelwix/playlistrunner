<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

class MainController extends AbstractController
{

    public function getAllProduits(){
        $solid = $this->getProduitsSolid();
        $liquid = $this->getProduitsLiquid();
        return array_merge($solid, $liquid);
    }

    public function getProduitsSolid(){
        return $produits = array(
            array('id' => 1, 'name' => 'T-shirt'),
            array('id' => 2, 'name' => 'Pantalon'),
            array('id' => 3, 'name' => 'Chaussures'),
            array('id' => 4, 'name' => 'Casquette'));
    }

    public function getProduitsLiquid(){
        return $voyages = array(
            array('id' => 1, 'name' => 'Voyage à Paris'),
            array('id' => 2, 'name' => 'Escapade à Bali'),
            array('id' => 3, 'name' => 'Aventure en Amazonie'),
            array('id' => 4, 'name' => 'Safari en Afrique'),
            array('id' => 5, 'name' => 'Croisière dans les Caraïbes'));
    }

    public function getThroughFilter($filter){
        switch ($filter){
            case 1:
                return $this->getAllProduits();
                break;
            case 2:
                return $this->getProduitsSolid();
                break;
            case 3:
                return $this->getProduitsLiquid();
        }
    }
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig', ['produits' => $this->getAllProduits()]);
    }

    #[Route('/platform', name: 'app_platform_pick')]
    public function platformPick(Request $request){

    }


    #[Route('/produit/ajax/filter/{filtre}', name: 'app_produit_filter')]
    public function filtrerLesProduits(Request $request, $filtre)
    {
        if($filtre == 10){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('dashboard/index.html.twig');
        }
        $products = $this->getThroughFilter($filtre);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->render('main/produit.html.twig', ['produits' => $products]);
    }

    #[Route('/produit/ajax/declinaison/{produit}', name: 'app_produit_declinaison')]
    public function getDeclinaisonList(Request $request, $produit)
    {
        if(in_array($produit, $this->getProduitsSolid())){
            dd("objet");
        }
        dd("liquid");

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->render('main/produit.html.twig', ['produits' => $products]);
    }

}
