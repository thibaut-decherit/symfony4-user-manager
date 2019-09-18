<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController
 * @package App\Controller
 */
class HomeController extends DefaultController
{
    /**
     * Renders homepage.
     *
     * @Route(name="home", methods="GET")
     * @return Response
     */
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }
}
