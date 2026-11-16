<?php

declare(strict_types=1);

namespace Bitrix30\Admin;

/** Доступ администратора без хранения открытого пароля. */
final readonly class AdminAccess
{
    public function __construct(
        private string $email,
        private string $passwordHash,
    ) {
    }

    public function isConfigured(): bool
    {
        if ($this->email === '' || $this->passwordHash === '') {
            return false;
        }

        $algorithm = password_get_info($this->passwordHash)['algo'];

        return \is_string($algorithm) && $algorithm !== '';
    }

    public function matches(string $email, string $password): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $passwordMatches = password_verify($password, $this->passwordHash);

        return hash_equals($this->email, $email) && $passwordMatches;
    }
}
