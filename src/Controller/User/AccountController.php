<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Form\User\AccountInformationType;
use App\Model\AbstractUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AccountController
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
    public function manage(): Response
    {
        return $this->render('user/account.html.twig');
    }

    /**
     * Renders the account information edit form
     *
     * @return Response
     */
    public function accountInformationForm(): Response
    {
        /**
         * @var AbstractUser $user
         */
        $user = $this->getUser();

        $form = $this->createForm(AccountInformationType::class, $user);

        return $this->render('form/user/_account_information.html.twig', [
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
    public function accountInformationEdit(Request $request, TranslatorInterface $translator): JsonResponse
    {
        $user = $this->getUser();

        $form = $this->createForm(AccountInformationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'account-information-edit-success',
                $translator->trans('flash.user.information_updated')
            );

            $form = $this->createForm(AccountInformationType::class, $user);
            $template = $this->render('form/user/_account_information.html.twig', [
                'form' => $form->createView()
            ]);
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate
            ], 200);
        }

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render('form/user/_account_information.html.twig', [
            'form' => $form->createView()
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 422 Unprocessable Entity status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 422);
    }
}
