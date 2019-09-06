<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LoginController
 * @package App\Controller\User
 */
class LoginController extends DefaultController
{
    /**
     * Handles login.
     *
     * @Route("/login", name="login", methods={"GET", "POST"})
     * @return Response
     */
    public function loginAction(): Response
    {
        return $this->render('User/login.html.twig');
    }
}
