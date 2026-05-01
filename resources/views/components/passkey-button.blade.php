@props([
    'action' => 'login',
    'label' => null,
    'size' => 'md',
    'fullWidth' => false,
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
    $sizeClasses = [
        'sm' => 'px-3 py-2 text-xs rounded-lg',
        'md' => 'px-4 py-2.5 text-sm rounded-xl',
        'lg' => 'px-5 py-3 text-sm rounded-xl',
    ][$size] ?? 'px-4 py-2.5 text-sm rounded-xl';
    $iconClasses = [
        'sm' => 'h-7 w-7',
        'md' => 'h-8 w-8',
        'lg' => 'h-9 w-9',
    ][$size] ?? 'h-8 w-8';
@endphp

<div @class([
    'flex flex-col gap-2',
    'w-full' => $fullWidth,
    'items-start' => ! $fullWidth,
])>
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
            'group relative inline-flex items-center justify-center gap-2 border border-slate-300 bg-white text-slate-800 shadow-sm transition duration-200 hover:-translate-y-px hover:border-slate-400 hover:bg-slate-50 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900/10 disabled:cursor-not-allowed disabled:opacity-60',
            'w-full' => $fullWidth,
            $sizeClasses,
        ]) }}
    >
        <span @class([
            'inline-flex items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-slate-700 transition group-hover:border-slate-300 group-hover:bg-slate-200',
            $iconClasses,
        ]) aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                <path d="M12 3l7 3v5c0 5.2-3.3 9.8-7 11-3.7-1.2-7-5.8-7-11V6l7-3Z" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M9.5 12.5 11 14l3.5-4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>

        <span class="whitespace-nowrap font-medium">{{ $slotContent }}</span>
    </button>

    @if($showStatus)
        <p data-vaultic-passkey-status class="text-xs text-slate-500 {{ $statusClass }}" aria-live="polite"></p>
    @endif
</div>

@include('vaultic::components.passkey-script')