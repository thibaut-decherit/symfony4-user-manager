<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Form\User\PasswordChangeType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function changeForm(): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(PasswordChangeType::class, $user);

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $this->getParameter('app.website_name'),
            $user->getUsername(),
            $user->getEmail()
        ];

        return $this->render('form/user/password-change.html.twig', [
            'form' => $form->createView(),
            'passwordBlacklist' => json_encode($passwordBlacklist)
        ]);
    }

    /**
     * Handles the password change form submitted with ajax.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TranslatorInterface $translator
     * @Route("/ajax", name="password_change_ajax", methods="POST")
     * @return JsonResponse
     */
    public function change(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $user = $this->getUser();

        $form = $this->createForm(PasswordChangeType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->setPassword($hashedPassword);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'password-change-success',
                $translator->trans('flash.user.password_updated')
            );

            $template = $this->render('form/user/password-change.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        // Renders and json encode the updated form (with errors)
        $template = $this->render('form/user/password-change.html.twig', [
            'form' => $form->createView()
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }
}
