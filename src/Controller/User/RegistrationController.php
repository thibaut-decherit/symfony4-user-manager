<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Entity\User;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
        $form = $this->createForm('App\Form\User\RegistrationType', $user);

        return $this->render('User/registration.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    /**
     * Handles the registration form submitted with ajax.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @Route("/register-ajax", name="registration_ajax", methods="POST")
     * @return JsonResponse
     * @throws Exception
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder): JsonResponse
    {
        $user = new User();
        $form = $this->createForm('App\Form\User\RegistrationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = $this->getDoctrine()->getManager()->getRepository('App:User');

            $duplicateUser = $userRepository->findOneBy(['email' => $user->getEmail()]);

            if (empty($duplicateUser)) {
                $this->handleSuccessfulRegistration($user, $passwordEncoder, $request->getLocale());
            } else {
                $this->handleDuplicateUserRegistration($duplicateUser, $request->getLocale());
            }

            // Renders and json encode the original form (required to empty form fields)
            $user = new User();
            $form = $this->createForm('App\Form\User\RegistrationType', $user);

            $this->addFlash(
                'registration-success',
                $this->get('translator')->trans('flash.user.registration_success')
            );

            $template = $this->render(':Form/User:registration.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render(':Form/User:registration.html.twig', [
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
     * @param string $locale
     */
    private function handleDuplicateUserRegistration(User $duplicateUser, string $locale): void
    {
        if ($duplicateUser->isActivated()) {
            $this->container->get('mailer.service')->registrationAttemptOnExistingVerifiedEmailAddress(
                $duplicateUser,
                $locale
            );
        } else {
            $this->container->get('mailer.service')->registrationAttemptOnExistingUnverifiedEmailAddress(
                $duplicateUser,
                $locale
            );
        }
    }

    /**
     * @param User $user
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param string $locale
     * @throws Exception
     */
    private function handleSuccessfulRegistration(
        User $user,
        UserPasswordEncoderInterface $passwordEncoder,
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

            $duplicate = $em->getRepository('App:User')->findOneBy(['accountActivationToken' => $token]);
            if (is_null($duplicate)) {
                $loop = false;
                $user->setAccountActivationToken($token);
            }
        }

        $this->container->get('mailer.service')->registrationSuccess($user, $locale);

        $em->persist($user);
        $em->flush();
    }
}
