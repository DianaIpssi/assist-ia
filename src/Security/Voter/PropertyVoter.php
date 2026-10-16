<?php

namespace App\Security\Voter;

use App\Entity\Agent;
use App\Entity\Property;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PropertyVoter extends Voter
{
    public const VIEW = 'PROPERTY_VIEW';
    public const EDIT = 'PROPERTY_EDIT';
    public const DELETE = 'PROPERTY_DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    // Fonction qui indique si ce voter gère l'action demandée sur un bien.
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Property;
    }

    // Fonction qui autorise l'action si le bien appartient à l'agent connecté ou si c'est un admin.
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Agent) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return $subject->getAgent() === $user;
    }
}
