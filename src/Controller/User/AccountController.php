<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Form\User\UserInformationType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ManageAccountController
 * @package App\Controller\User
 *
 * @Route("/account")
 */
class AccountController extends DefaultController
{
    /**
     * Renders user account view.
     *
     * @Route(name="account", methods="GET")
     * @return Response
     */
    public function manageAction(): Response
    {
        return $this->render('user/account.html.twig');
    }

    /**
     * Renders the account information edit form
     *
     * @return Response
     */
    public function accountInformationFormAction(): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserInformationType::class, $user);

        return $this->render('form/user/account-information.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Handles the account information edit form submitted with ajax.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @Route("/account-information-edit-ajax", name="account_information_edit_ajax", methods="POST")
     * @return JsonResponse
     */
    public function accountInformationEditAction(Request $request, TranslatorInterface $translator): JsonResponse
    {
        $user = $this->getUser();

        $form = $this->createForm(UserInformationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'account-information-edit-success',
                $translator->trans('flash.user.information_updated')
            );

            $template = $this->render('form/user/account-information.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        /*
         * $user must be refreshed or invalid POST data (username) will conflict with logged-in user and Symfony will
         * logout the user.
         * See https://symfony.com/doc/current/security/user_provider.html#understanding-how-users-are-refreshed-from-the-session
         */
        $this->getDoctrine()->getManager()->refresh($user);

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render('form/user/account-information.html.twig', [
            'form' => $form->createView()
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }
}
