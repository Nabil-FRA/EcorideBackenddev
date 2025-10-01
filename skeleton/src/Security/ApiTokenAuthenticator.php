<?php

namespace App\Security;

use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private UtilisateurRepository $utilisateurRepository;

    public function __construct(UtilisateurRepository $utilisateurRepository)
    {
        $this->utilisateurRepository = $utilisateurRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization'); // Vérifie si le token est présent dans le header
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $apiToken = $request->headers->get('Authorization');

        if (!$apiToken) {
            throw new AuthenticationException('No API token provided');
        }

        // ✅ Nettoie le token reçu (enlève "Bearer ")
        $apiToken = str_replace('Bearer ', '', $apiToken);

        // Recherche l'utilisateur par son token
        $user = $this->utilisateurRepository->findOneBy(['apiToken' => $apiToken]);

        if (!$user) {
            throw new AuthenticationException('Invalid API token');
        }

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), function () use ($user) {
            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // Authentification réussie, rien à faire ici
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_UNAUTHORIZED);
    }
}
