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
    $buttonLabel = $label ?: ($mode === 'register' ? 'Create a passkey' : 'Continue with passkey');
    $slotContent = isset($slot) && method_exists($slot, 'isEmpty') && ! $slot->isEmpty() ? $slot : $buttonLabel;
@endphp

<div class="w-full">
    @once
        <style>
            .vaultic-passkey-btn {
                width: 100%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.7rem;
                border: 1px solid #0f172a;
                border-radius: 14px;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                color: #ffffff;
                padding: 0.8rem 1rem;
                font-size: 0.95rem;
                font-weight: 600;
                letter-spacing: 0.01em;
                cursor: pointer;
                box-shadow: 0 16px 32px -18px rgba(15, 23, 42, 0.75);
                transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
            }

            .vaultic-passkey-btn:hover {
                transform: translateY(-1px);
                filter: brightness(1.05);
                box-shadow: 0 22px 40px -20px rgba(15, 23, 42, 0.85);
            }

            .vaultic-passkey-btn:focus-visible {
                outline: 2px solid #0f172a;
                outline-offset: 2px;
            }

            .vaultic-passkey-btn:disabled {
                cursor: not-allowed;
                opacity: 0.65;
                transform: none;
                box-shadow: none;
            }

            .vaultic-passkey-btn__icon {
                width: 2rem;
                height: 2rem;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.14);
            }

            .vaultic-passkey-status {
                margin-top: 0.6rem;
                font-size: 0.88rem;
                color: #475569;
            }
        </style>
    @endonce

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
        {{ $attributes->class(['vaultic-passkey-btn']) }}
    >
        <span class="vaultic-passkey-btn__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                <path d="M12 3l7 3v5c0 5.2-3.3 9.8-7 11-3.7-1.2-7-5.8-7-11V6l7-3Z" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M9.5 12.5 11 14l3.5-4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>

        <span>{{ $slotContent }}</span>
    </button>

    @if($showStatus)
        <p data-vaultic-passkey-status class="vaultic-passkey-status {{ $statusClass }}" aria-live="polite"></p>
    @endif
</div>

@include('vaultic::components.passkey-script')