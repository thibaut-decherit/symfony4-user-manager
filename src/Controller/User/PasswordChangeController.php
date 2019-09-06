<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class PasswordChangeController
 * @package App\Controller\User
 *
 * @Route("/account/password-change")
 */
class PasswordChangeController extends DefaultController
{
    /**
     * Renders the password change form.
     *
     * @return Response
     */
    public function changeFormAction(): Response
    {
        $user = $this->getUser();

        $form = $this->createForm('App\Form\User\PasswordChangeType', $user);

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $user->getUsername(),
            $user->getEmail()
        ];

        return $this->render(':Form/User:password-change.html.twig', [
            'form' => $form->createView(),
            'passwordBlacklist' => json_encode($passwordBlacklist)
        ]);
    }

    /**
     * Handles the password change form submitted with ajax.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @Route("/ajax", name="password_change_ajax", methods="POST")
     * @return JsonResponse
     */
    public function changeAction(Request $request, UserPasswordEncoderInterface $passwordEncoder): JsonResponse
    {
        $user = $this->getUser();

        $form = $this->createForm('App\Form\User\PasswordChangeType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->setPassword($hashedPassword);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'password-change-success',
                $this->get('translator')->trans('flash.user.password_updated')
            );

            $template = $this->render(':Form/User:password-change.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        /*
         * $user must be refreshed or invalid POST data will conflict with logged-in user and crash the session,
         * this line is not needed when editing with ajax any other entity than User
         */
        $this->getDoctrine()->getManager()->refresh($user);

        // Renders and json encode the updated form (with errors)
        $template = $this->render(':Form/User:password-change.html.twig', [
            'form' => $form->createView()
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }
}
