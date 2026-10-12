<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Diagnostic command to inspect Vaultic configuration and infrastructure readiness.
 */
class HealthCheckCommand extends Command
{
    /** @var string */
    protected $signature = 'vaultic:health';

    /** @var string */
    protected $description = 'Perform diagnostic health checks on Vaultic configuration and infrastructure.';

    public function handle(): int
    {
        $this->info('Running Vaultic diagnostic health checks...');

        $checks = [
            $this->checkRelyingPartyConfig(),
            $this->checkSecureContext(),
            $this->checkCacheConnectivity(),
            $this->checkDatabaseSchema(),
            $this->checkUserModelConfig(),
        ];

        $headers = ['Check', 'Status', 'Details'];
        $rows = array_map(fn (array $c): array => [
            $c['name'],
            $c['passed'] ? 'OK' : 'FAIL',
            $c['details'],
        ], $checks);

        $this->table($headers, $rows);

        $hasFailure = in_array(false, array_column($checks, 'passed'), true);

        if ($hasFailure) {
            $this->error('One or more health checks failed. Review the details above.');

            return self::FAILURE;
        }

        $this->info('All health checks passed successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array{name: string, passed: bool, details: string}
     */
    private function checkRelyingPartyConfig(): array
    {
        $rpId = config('vaultic.rp.id');
        $rpName = config('vaultic.rp.name');

        if (empty($rpId) || empty($rpName)) {
            return [
                'name' => 'Relying Party (RP)',
                'passed' => false,
                'details' => 'RP ID or RP Name is not set in vaultic config.',
            ];
        }

        return [
            'name' => 'Relying Party (RP)',
            'passed' => true,
            'details' => sprintf('ID: %s | Name: %s', (string) $rpId, (string) $rpName),
        ];
    }

    /**
     * @return array{name: string, passed: bool, details: string}
     */
    private function checkSecureContext(): array
    {
        $rpId = (string) config('vaultic.rp.id', '');
        $appUrl = (string) config('app.url', 'http://localhost');
        $scheme = (string) parse_url($appUrl, PHP_URL_SCHEME);
        $host = (string) parse_url($appUrl, PHP_URL_HOST);

        $isLocalhost = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || in_array($rpId, ['localhost', '127.0.0.1'], true);

        if ($scheme !== 'https' && ! $isLocalhost) {
            return [
                'name' => 'Secure Context (HTTPS)',
                'passed' => false,
                'details' => 'WebAuthn requires HTTPS for non-localhost domains.',
            ];
        }

        return [
            'name' => 'Secure Context (HTTPS)',
            'passed' => true,
            'details' => $isLocalhost ? 'Local development environment detected.' : 'HTTPS scheme configured.',
        ];
    }

    /**
     * @return array{name: string, passed: bool, details: string}
     */
    private function checkCacheConnectivity(): array
    {
        try {
            $storeName = config('vaultic.cache.store');
            $cache = is_string($storeName) && $storeName !== ''
                ? Cache::store($storeName)
                : Cache::store();

            $testKey = 'vaultic:health:check:'.bin2hex(random_bytes(8));
            $cache->put($testKey, 'ok', 10);
            $retrieved = $cache->get($testKey);
            $cache->forget($testKey);

            if ($retrieved !== 'ok') {
                return [
                    'name' => 'Cache Store',
                    'passed' => false,
                    'details' => 'Cache store failed to persist test key.',
                ];
            }

            return [
                'name' => 'Cache Store',
                'passed' => true,
                'details' => sprintf('Connected to store [%s].', (string) ($storeName ?: 'default')),
            ];
        } catch (Throwable $e) {
            return [
                'name' => 'Cache Store',
                'passed' => false,
                'details' => 'Cache connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{name: string, passed: bool, details: string}
     */
    private function checkDatabaseSchema(): array
    {
        try {
            DB::connection()->getPdo();

            if (! Schema::hasTable('passkeys')) {
                return [
                    'name' => 'Database Table',
                    'passed' => false,
                    'details' => 'The [passkeys] table is missing. Run php artisan migrate.',
                ];
            }

            return [
                'name' => 'Database Table',
                'passed' => true,
                'details' => 'Table [passkeys] exists and database connection is active.',
            ];
        } catch (Throwable $e) {
            return [
                'name' => 'Database Table',
                'passed' => false,
                'details' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{name: string, passed: bool, details: string}
     */
    private function checkUserModelConfig(): array
    {
        $userModel = (string) config('vaultic.user_model', '');

        if ($userModel === '' || ! class_exists($userModel)) {
            return [
                'name' => 'User Model',
                'passed' => false,
                'details' => sprintf('Configured user model [%s] does not exist.', $userModel),
            ];
        }

        if (! is_subclass_of($userModel, Authenticatable::class)) {
            return [
                'name' => 'User Model',
                'passed' => false,
                'details' => sprintf('Model [%s] does not implement Authenticatable.', $userModel),
            ];
        }

        return [
            'name' => 'User Model',
            'passed' => true,
            'details' => sprintf('Class [%s] implements Authenticatable.', $userModel),
        ];
    }
}
