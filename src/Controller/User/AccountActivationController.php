<?php

namespace App\Controller\User;

use App\Controller\DefaultController;
use App\Helper\StringHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AccountActivationController
 * @package App\Controller\User
 */
class AccountActivationController extends DefaultController
{
    /**
     * Renders account activation confirmation view where user can click a button to confirm the activation.
     *
     * @param Request $request
     * @Route("/activate-account/confirm", name="account_activation_confirm", methods="GET")
     * @return RedirectResponse
     */
    public function confirmAction(Request $request): Response
    {
        $accountActivationToken = $request->get('token');

        if (empty($accountActivationToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App:User')->findOneBy([
            'accountActivationToken' => StringHelper::truncateToMySQLVarcharMaxLength($accountActivationToken)
        ]);

        if ($user === null) {
            return $this->redirectToRoute('home');
        }

        return $this->render('user/account-activation-confirm.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Activates account matching activation token.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @Route("/activate-account/activate", name="account_activation_activate", methods="POST")
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function activateAction(Request $request, TranslatorInterface $translator): RedirectResponse
    {
        if ($this->isCsrfTokenValid('account_activation_activate', $request->get('_csrf_token')) === false) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $accountActivationToken = $request->get('account_activation_token');

        if (empty($accountActivationToken)) {
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App:User')->findOneBy([
            'accountActivationToken' => StringHelper::truncateToMySQLVarcharMaxLength($accountActivationToken)
        ]);

        $this->addFlash(
            'account-activation-success',
            $translator->trans('flash.user.account_activated_successfully')
        );

        if ($user !== null && $user->isActivated() === false) {
            $user->setActivated(true);
            $user->setAccountActivationToken(null);

            $em->flush();
        }

        return $this->redirectToRoute('login');
    }
}
