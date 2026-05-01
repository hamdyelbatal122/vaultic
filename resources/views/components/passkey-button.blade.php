@props([
    'action' => 'login',
    'label' => null,
    'identifier' => null,
    'identifierSelector' => null,
    'name' => null,
    'nameSelector' => null,
    'guard' => null,
    'optionsUrl' => null,
    'submitUrl' => null,
    'redirectTo' => null,
    'reloadOnSuccess' => false,
    'showStatus' => true,
    'statusClass' => 'mt-3 text-sm text-slate-500',
])

@php
    $mode = $action === 'register' ? 'register' : 'login';
    $namePrefix = rtrim((string) config('vaultic.routes.web.name_prefix', 'vaultic.'), '.').'.';
    $defaultOptionsRoute = $mode === 'register' ? $namePrefix.'register.options' : $namePrefix.'authenticate.options';
    $defaultSubmitRoute = $mode === 'register' ? $namePrefix.'register.store' : $namePrefix.'authenticate.store';
    $resolvedOptionsUrl = $optionsUrl ?: (\Illuminate\Support\Facades\Route::has($defaultOptionsRoute) ? route($defaultOptionsRoute) : '#');
    $resolvedSubmitUrl = $submitUrl ?: (\Illuminate\Support\Facades\Route::has($defaultSubmitRoute) ? route($defaultSubmitRoute) : '#');
    $buttonLabel = $label ?: ($mode === 'register' ? 'Create a passkey' : 'FIDO2/WebAuthn Login');
    $slotContent = isset($slot) && method_exists($slot, 'isEmpty') && ! $slot->isEmpty() ? $slot : $buttonLabel;
@endphp

<div class="w-full">
    <button
        type="button"
        data-vaultic-passkey
        data-vaultic-action="{{ $mode }}"
        data-options-url="{{ $resolvedOptionsUrl }}"
        data-submit-url="{{ $resolvedSubmitUrl }}"
        data-csrf-token="{{ csrf_token() }}"
        @if($identifier !== null) data-identifier="{{ $identifier }}" @endif
        @if($identifierSelector) data-identifier-selector="{{ $identifierSelector }}" @endif
        @if($name !== null) data-name="{{ $name }}" @endif
        @if($nameSelector) data-name-selector="{{ $nameSelector }}" @endif
        @if($guard !== null) data-guard="{{ $guard }}" @endif
        @if($redirectTo) data-redirect-to="{{ $redirectTo }}" @endif
        data-reload-on-success="{{ $reloadOnSuccess ? 'true' : 'false' }}"
        {{ $attributes->class([
            'group inline-flex items-center justify-center gap-3 rounded-2xl border border-slate-700 bg-slate-800 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/15 transition duration-200 hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-300 disabled:cursor-not-allowed disabled:opacity-60',
            'w-full' => ! $attributes->has('class'),
        ]) }}
    >
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/10 text-slate-100">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                <path d="M12 3l7 3v5c0 5.2-3.3 9.8-7 11-3.7-1.2-7-5.8-7-11V6l7-3Z" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M9.5 12.5 11 14l3.5-4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>

        <span>{{ $slotContent }}</span>
    </button>

    @if($showStatus)
        <p data-vaultic-passkey-status class="{{ $statusClass }}" aria-live="polite"></p>
    @endif
</div>

@include('vaultic::components.passkey-script')