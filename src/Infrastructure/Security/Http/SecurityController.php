<?php

namespace App\Infrastructure\Security\Http;

use App\Infrastructure\Security\Keycloak\KeycloakAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Sends the browser to Keycloak's login page, unless the last attempt failed: the error is then
     * shown with a retry button, instead of bouncing straight back to Keycloak.
     */
    #[Route(path: '/admin/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, KeycloakAuthenticator $keycloakAuthenticator): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        if (null === $error) {
            return $keycloakAuthenticator->start($request);
        }

        return $this->render('security/login.html.twig', [
            'error' => $error,
        ]);
    }

    /** Keycloak redirects here after login; handled by KeycloakAuthenticator before reaching the controller. */
    #[Route(path: '/admin/login/check', name: KeycloakAuthenticator::CHECK_ROUTE)]
    public function check(): never
    {
        throw new \LogicException('This route is handled by the admin firewall\'s Keycloak authenticator.');
    }

    #[Route(path: '/admin/logout', name: 'app_logout')]
    public function logout(): Response
    {
        return $this->redirectToRoute('app_login');
    }
}
