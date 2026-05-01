<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('blocks sensitive routes when no passkey session exists', function (): void {
    Route::middleware(['web', 'passkey.required'])->get('/_vaultic/protected', static fn () => 'ok');

    $this->get('/_vaultic/protected')->assertForbidden();
});

it('allows sensitive routes after passkey session is set', function (): void {
    Route::middleware(['web', 'passkey.required'])->get('/_vaultic/allowed', static fn () => 'ok');

    $this->withSession(['vaultic.passkeys.authenticated' => true])
        ->get('/_vaultic/allowed')
        ->assertOk()
        ->assertSee('ok');
});
