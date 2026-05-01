<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Data;

class AssertionResult
{
    public function __construct(
        private string $credentialId,
        private int $signCount,
    ) {
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function getSignCount(): int
    {
        return $this->signCount;
    }
}
