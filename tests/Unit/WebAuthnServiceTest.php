<?php

namespace Hamzi\Vaultic\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Hamzi\Vaultic\Contracts\ApiTokenIssuer;
use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Data\AssertionResult;
use Hamzi\Vaultic\Data\RegistrationResult;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Repositories\EloquentPasskeyRepository;
use Hamzi\Vaultic\Services\ChallengeStore;
use Hamzi\Vaultic\Services\WebAuthnService;
use Hamzi\Vaultic\Tests\Fixtures\AdminUser;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;

class WebAuthnServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
        });

        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->string('authenticatable_type');
            $table->string('authenticatable_id', 191);
            $table->string('name')->default('Unnamed device');
            $table->string('credential_id')->unique();
            $table->longText('public_key');
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->string('transports')->nullable();
            $table->string('aaguid', 36)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        config()->set('vaultic.auth.default_guard', 'web');
        config()->set('vaultic.auth.session_key', 'vaultic.passkeys.authenticated');
        config()->set('vaultic.auth.guards.web', [
            'guard' => 'web',
            'provider_model' => TestUser::class,
            'identifier_column' => 'email',
            'stateful' => true,
            'remember' => false,
        ]);
        config()->set('vaultic.auth.guards.api', [
            'guard' => 'api',
            'provider_model' => TestUser::class,
            'identifier_column' => 'email',
            'stateful' => false,
        ]);
        config()->set('vaultic.auth.guards.admin', [
            'guard' => 'admin',
            'provider_model' => AdminUser::class,
            'identifier_column' => 'email',
            'stateful' => true,
        ]);
        config()->set('vaultic.rp.id', 'example.test');
    }

    public function test_it_authenticates_statefully_for_a_session_guard()
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Session User',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Laptop',
            'credential_id' => 'cred-web',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $service = $this->makeService(new class implements ApiTokenIssuer {
            public function issueToken($authenticatable, $guardName, array $payload = [])
            {
                return [];
            }
        });

        Auth::shouldReceive('guard')->once()->with('web')->andReturn(new class {
            public function login($user, $remember = false)
            {
            }
        });

        $service->buildAuthenticationOptions('user@example.com', 'web');
        $result = $service->authenticate('user@example.com', ['id' => 'cred-web'], 'web', true);

        $this->assertSame(200, $result['status']);
        $this->assertSame('web', $result['body']['guard']);
        $this->assertTrue($result['body']['stateful']);
        $this->assertArrayHasKey('vaultic.passkeys.authenticated', $result['session']);
        $this->assertSame([], $result['body']['tokens']);
    }

    public function test_it_returns_token_payload_for_stateless_api_guard()
    {
        $user = TestUser::query()->create([
            'email' => 'api@example.com',
            'name' => 'Api User',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Phone',
            'credential_id' => 'cred-api',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $service = $this->makeService(new class implements ApiTokenIssuer {
            public function issueToken($authenticatable, $guardName, array $payload = [])
            {
                return [
                    'access_token' => 'token-123',
                    'token_type' => 'Bearer',
                    'guard' => $guardName,
                ];
            }
        });

        $service->buildAuthenticationOptions('api@example.com', 'api');
        $result = $service->authenticate('api@example.com', ['id' => 'cred-api'], 'api', false);

        $this->assertSame(200, $result['status']);
        $this->assertSame('api', $result['body']['guard']);
        $this->assertFalse($result['body']['stateful']);
        $this->assertSame('token-123', $result['body']['tokens']['access_token']);
        $this->assertSame([], $result['session']);
    }

    public function test_it_builds_authentication_options_for_a_different_guard_model()
    {
        $admin = AdminUser::query()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => AdminUser::class,
            'authenticatable_id' => (string) $admin->getAuthIdentifier(),
            'name' => 'Security Key',
            'credential_id' => 'cred-admin',
            'public_key' => 'public-key',
            'sign_count' => 0,
        ]);

        $service = $this->makeService(new class implements ApiTokenIssuer {
            public function issueToken($authenticatable, $guardName, array $payload = [])
            {
                return [];
            }
        });

        $options = $service->buildAuthenticationOptions('admin@example.com', 'admin');

        $this->assertSame('admin', $options['vaultic']['guard']);
        $this->assertCount(1, $options['allowCredentials']);
        $this->assertSame('cred-admin', $options['allowCredentials'][0]['id']);
    }

    public function test_it_authenticates_without_identifier_using_discoverable_passkey()
    {
        $user = TestUser::query()->create([
            'email' => 'discoverable@example.com',
            'name' => 'Discoverable User',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Security Key',
            'credential_id' => 'cred-discoverable',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $service = $this->makeService(new class implements ApiTokenIssuer {
            public function issueToken($authenticatable, $guardName, array $payload = [])
            {
                return [];
            }
        });

        Auth::shouldReceive('guard')->once()->with('web')->andReturn(new class {
            public function login($user, $remember = false)
            {
            }
        });

        $options = $service->buildAuthenticationOptions(null, 'web');

        $this->assertArrayHasKey('vaultic', $options);
        $this->assertArrayHasKey('challenge_key', $options['vaultic']);
        $this->assertSame([], $options['allowCredentials']);

        $result = $service->authenticate(null, [
            'id' => 'cred-discoverable',
            'challenge_key' => $options['vaultic']['challenge_key'],
        ], 'web', true);

        $this->assertSame(200, $result['status']);
        $this->assertSame('web', $result['body']['guard']);
        $this->assertTrue($result['body']['stateful']);
    }

    /**
     * @param ApiTokenIssuer $tokenIssuer
     * @return WebAuthnService
     */
    private function makeService(ApiTokenIssuer $tokenIssuer)
    {
        $verifier = new class implements WebAuthnVerifier {
            public function verifyRegistration(array $payload, string $challenge, string $rpId): RegistrationResult
            {
                return new RegistrationResult('cred-register', 'public-key', 0);
            }

            public function verifyAssertion(array $payload, string $challenge, string $rpId): AssertionResult
            {
                return new AssertionResult((string) $payload['id'], 2);
            }
        };

        return new WebAuthnService(
            $verifier,
            new ChallengeStore(new Repository(new ArrayStore()), 'vaultic:test:', 60),
            new EloquentPasskeyRepository(),
            $tokenIssuer
        );
    }
}
