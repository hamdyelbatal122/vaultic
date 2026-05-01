<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Data;

final readonly class RegistrationResult
{
    public function __construct(
        public string $credentialId,
        public string $publicKey,
        public int $signCount,
        public string $transports = '',
        public string $aaguid = '',
    ) {
    }
}
