<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Form\User\PasswordChangeType;
use App\Model\AbstractUser;
use App\Service\RememberMeCookieService;
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
        /**
         * @var AbstractUser $user
         */
        $user = $this->getUser();

        $form = $this->createForm(PasswordChangeType::class, $user);

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $this->getParameter('app.website_name'),
            $user->getBusinessUsername(),
            $user->getEmail()
        ];

        return $this->render('form/user/_password_change.html.twig', [
            'form' => $form->createView(),
            'password_blacklist' => json_encode($passwordBlacklist)
        ]);
    }

    /**
     * Handles the password change form submitted with ajax.
     *
     * @param Request $request
     * @param RememberMeCookieService $rememberMeCookieService
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TranslatorInterface $translator
     * @Route("-ajax", name="password_change_ajax", methods="POST")
     * @return JsonResponse
     */
    public function change(
        Request $request,
        RememberMeCookieService $rememberMeCookieService,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator
    ): JsonResponse
    {
        /**
         * @var AbstractUser $user
         */
        $user = $this->getUser();

        $form = $this->createForm(PasswordChangeType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hasValidRememberMeCookie = $rememberMeCookieService->hasValidCookie($request);
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->setPassword($hashedPassword);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'password-change-success',
                $translator->trans('flash.user.password_updated')
            );

            $form = $this->createForm(PasswordChangeType::class, $user);
            $template = $this->render('form/user/_password_change.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            $jsonResponse = new JsonResponse([
                'template' => $jsonTemplate
            ], 200);

            // Changing password invalids current remember me cookie so it must be refreshed.
            if ($hasValidRememberMeCookie) {
                $rememberMeCookieService->setCookie($jsonResponse, $user, $request);
            }

            return $jsonResponse;
        }

        // Renders and json encode the updated form (with errors)
        $template = $this->render('form/user/_password_change.html.twig', [
            'form' => $form->createView()
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 422 Unprocessable Entity status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 422);
    }
}
