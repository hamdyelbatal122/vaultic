<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Data;

readonly class RegistrationResult
{
    public function __construct(
        private string $credentialId,
        private string $publicKey,
        private int $signCount,
        private string $transports = '',
        private string $aaguid = '',
    ) {
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getSignCount(): int
    {
        return $this->signCount;
    }

    public function getTransports(): string
    {
        return $this->transports;
    }

    public function getAaguid(): string
    {
        return $this->aaguid;
    }
}
