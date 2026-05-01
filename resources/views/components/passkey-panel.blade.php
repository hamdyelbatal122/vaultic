@props([
    'guard' => null,
    'title' => 'Manage Passkeys',
    'description' => 'Register a new passkey, review your current authenticators, and remove devices you no longer trust from one clean security surface.',
    'registerTitle' => 'Register a new authenticator',
    'registerDescription' => 'Use Face ID, Touch ID, Windows Hello, a nearby phone through hybrid transport, or a FIDO2 security key.',
    'registerLabel' => 'Add this device as a passkey',
    'registerPlaceholder' => 'MacBook Pro, iPhone, Security Key',
    'emptyTitle' => 'No passkeys registered yet',
    'emptyDescription' => 'Create your first passkey to unlock passwordless sign-in for this account.',
    'passkeys' => null,
    'user' => null,
])

@php
    $resolvedGuard = $guard ?: (string) config('vaultic.auth.default_guard', 'web');
    $resolvedUser = $user ?: auth()->guard($resolvedGuard)->user();
    $resolvedPasskeys = $passkeys;

    if ($resolvedPasskeys === null) {
        $resolvedPasskeys = $resolvedUser
            ? app(\Hamzi\Vaultic\Contracts\PasskeyRepository::class)->listForAuthenticatable($resolvedUser)
            : collect();
    }

    $deleteRouteName = rtrim((string) config('vaultic.routes.web.name_prefix', 'vaultic.'), '.').'.passkeys.destroy';
    $nameFieldId = 'vaultic-passkey-name-'.substr(md5((string) $resolvedGuard), 0, 8);
@endphp

<section class="mx-auto w-full max-w-5xl overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-[0_30px_100px_-40px_rgba(15,23,42,0.45)]">
    <div class="grid gap-0 lg:grid-cols-[1.1fr,0.9fr]">
        <div class="border-b border-slate-200 bg-[linear-gradient(135deg,rgba(15,23,42,0.04),rgba(148,163,184,0.02))] p-6 sm:p-8 lg:border-b-0 lg:border-r">
            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                Security Center
            </span>

            <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">{{ $title }}</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>

            @if (session('vaultic.status'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('vaultic.status') }}
                </div>
            @endif

            @if (! $resolvedUser)
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                    Sign in first to register or manage passkeys for this account.
                </div>
            @else
                <div class="mt-8 rounded-[28px] border border-slate-200 bg-slate-50 p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-950">{{ $registerTitle }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $registerDescription }}</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 shadow-sm">
                            {{ $resolvedGuard }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium text-slate-600">
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Discoverable passkeys</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Biometrics</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Phone sign-in</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Security keys</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        <label class="block text-sm font-medium text-slate-700" for="{{ $nameFieldId }}">Device label</label>
                        <input
                            id="{{ $nameFieldId }}"
                            type="text"
                            name="name"
                            class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-200"
                            placeholder="{{ $registerPlaceholder }}"
                        />
                    </div>

                    <div class="mt-5">
                        <x-vaultic::passkey-button
                            action="register"
                            name-selector="#{{ $nameFieldId }}"
                            :guard="$resolvedGuard"
                            :label="$registerLabel"
                            :full-width="true"
                            reload-on-success="true"
                        />
                    </div>
                </div>
            @endif
        </div>

        <div class="p-6 sm:p-8">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-xl font-semibold text-slate-950">Registered authenticators</h3>
                    <p class="mt-1 text-sm text-slate-500">Review every passkey currently linked to this account.</p>
                </div>
                <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                    {{ $resolvedPasskeys->count() }} total
                </div>
            </div>

            @if ($resolvedPasskeys->isEmpty())
                <div class="mt-6 rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                    <h4 class="text-lg font-semibold text-slate-900">{{ $emptyTitle }}</h4>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-600">{{ $emptyDescription }}</p>
                </div>
            @else
                <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm text-slate-700">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <tr>
                                    <th class="px-5 py-4">Device</th>
                                    <th class="px-5 py-4">Credential</th>
                                    <th class="px-5 py-4">Last used</th>
                                    <th class="px-5 py-4">Added</th>
                                    <th class="px-5 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white align-top">
                                @foreach ($resolvedPasskeys as $passkey)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-900">{{ $passkey->name }}</div>
                                            @if ($passkey->aaguid)
                                                <div class="mt-1 text-xs text-slate-500">AAGUID {{ $passkey->aaguid }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($passkey->credential_id, 24) }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ optional($passkey->last_used_at)->diffForHumans() ?: 'Never used' }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ optional($passkey->created_at)->format('Y-m-d H:i') ?: 'Unknown' }}</td>
                                        <td class="px-5 py-4 text-right">
                                            @if (\Illuminate\Support\Facades\Route::has($deleteRouteName))
                                                <form method="POST" action="{{ route($deleteRouteName, $passkey) }}" class="inline-flex">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100"
                                                    >
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>