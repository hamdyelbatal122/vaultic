<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Data;

final readonly class AssertionResult
{
    public function __construct(
        public string $credentialId,
        public int $signCount,
    ) {
    }
}
