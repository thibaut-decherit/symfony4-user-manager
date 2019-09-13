<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Entity\User;
use App\Form\User\RegistrationType;
use App\Service\MailerService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class RegistrationController
 * @package App\Controller\User
 */
class RegistrationController extends DefaultController
{
    /**
     * Renders the initial registration form.
     *
     * @Route("/register", name="registration", methods="GET")
     * @return Response
     */
    public function registerFormAction(): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $this->getParameter('website_name')
        ];

        return $this->render('user/registration.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'passwordBlacklist' => json_encode($passwordBlacklist)
        ]);
    }

    /**
     * Handles the registration form submitted with ajax.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param MailerService $mailer
     * @param TranslatorInterface $translator
     * @Route("/register-ajax", name="registration_ajax", methods="POST")
     * @return JsonResponse
     * @throws Exception
     */
    public function registerAction(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        MailerService $mailer,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = $this->getDoctrine()->getManager()->getRepository(User::class);

            $duplicateUser = $userRepository->findOneBy(['email' => $user->getEmail()]);

            if (empty($duplicateUser)) {
                $this->handleSuccessfulRegistration($user, $passwordEncoder, $mailer, $request->getLocale());
            } else {
                $this->handleDuplicateUserRegistration($duplicateUser, $mailer, $request->getLocale());
            }

            // Renders and json encode the original form (required to empty form fields)
            $user = new User();
            $form = $this->createForm(RegistrationType::class, $user);

            $this->addFlash(
                'registration-success',
                $translator->trans('flash.user.registration_success')
            );

            $template = $this->render('form/user/registration.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render('form/user/registration.html.twig', [
            'form' => $form->createView(),
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }

    /**
     * Sends an email to existing user if registration attempt with already registered email address.
     *
     * @param User $duplicateUser
     * @param MailerService $mailer
     * @param string $locale
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function handleDuplicateUserRegistration(
        User $duplicateUser,
        MailerService $mailer,
        string $locale
    ): void
    {
        if ($duplicateUser->isActivated()) {
            $mailer->registrationAttemptOnExistingVerifiedEmailAddress(
                $duplicateUser,
                $locale
            );
        } else {
            $mailer->registrationAttemptOnExistingUnverifiedEmailAddress(
                $duplicateUser,
                $locale
            );
        }
    }

    /**
     * @param User $user
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param MailerService $mailer
     * @param string $locale
     * @throws Exception
     */
    private function handleSuccessfulRegistration(
        User $user,
        UserPasswordEncoderInterface $passwordEncoder,
        MailerService $mailer,
        string $locale
    ): void
    {
        $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

        $em = $this->getDoctrine()->getManager();
        $user->setPassword($hashedPassword);

        // Generates activation token and retries if token already exists.
        $loop = true;
        while ($loop) {
            $token = $user->generateSecureToken();

            $duplicate = $em->getRepository(User::class)->findOneBy(['accountActivationToken' => $token]);
            if (is_null($duplicate)) {
                $loop = false;
                $user->setAccountActivationToken($token);
            }
        }

        $mailer->registrationSuccess($user, $locale);

        $em->persist($user);
        $em->flush();
    }
}
