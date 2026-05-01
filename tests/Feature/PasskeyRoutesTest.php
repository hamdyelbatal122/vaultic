<?php

declare(strict_types=1);

it('registers passkey routes', function (): void {
    expect(route('vaultic.register.options', absolute: false))->toContain('/passkeys/register/options');
    expect(route('vaultic.authenticate.store', absolute: false))->toContain('/passkeys/authenticate');
});
