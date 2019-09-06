<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ManageAccountController
 * @package AppBundle\Controller\User
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
        return $this->render(':User:account.html.twig');
    }

    /**
     * Renders the account information edit form
     *
     * @return Response
     */
    public function accountInformationFormAction(): Response
    {
        $user = $this->getUser();

        $form = $this->createForm('AppBundle\Form\User\UserInformationType', $user);

        return $this->render(':Form/User:account-information.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Handles the account information edit form submitted with ajax.
     *
     * @param Request $request
     * @Route("/account-information-edit-ajax", name="account_information_edit_ajax", methods="POST")
     * @return JsonResponse
     */
    public function accountInformationEditAction(Request $request): JsonResponse
    {
        $user = $this->getUser();

        $form = $this->createForm('AppBundle\Form\User\UserInformationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'account-information-edit-success',
                $this->get('translator')->trans('flash.user.information_updated')
            );

            $template = $this->render(':Form/User:account-information.html.twig', [
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

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render(':Form/User:account-information.html.twig', [
            'form' => $form->createView()
        ]);
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }
}
