<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionUserService
{
    public function __construct(
        private SessionInterface $session,
        private UserRepository $userRepository
    ) {}

    public function getCurrentUser(): ?User
    {
        $id = $this->session->get('user_id');
        if (!$id) return null;
        return $this->userRepository->find($id);
    }

    public function isLoggedIn(): bool
    {
        return (bool) $this->session->get('user_id');
    }

    public function isAdmin(): bool
    {
        return $this->session->get('user_role') === 'admin';
    }

    public function logout(): void
    {
        $this->session->clear();
    }

    public function getUserInitials(): string
    {
        $user = $this->getCurrentUser();
        if (!$user) return '?';
        $p = $user->getPrenom() ?? '';
        $n = $user->getNom()    ?? '';
        return strtoupper(
            ($p ? $p[0] : '') . ($n ? $n[0] : '')
        ) ?: '?';
    }

    public function setUser(User $user): void
    {
        $this->session->set('user_id',   $user->getIdUser());
        $this->session->set('user_role', $user->getRole());
    }
}