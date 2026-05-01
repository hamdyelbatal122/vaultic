<div class="mx-auto w-full max-w-lg rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_24px_80px_-36px_rgba(15,23,42,0.42)] sm:p-8">
    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
        Passwordless Access
    </div>

    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950">Sign in with a passkey</h2>
    <p class="mt-2 text-sm leading-6 text-slate-600">
        Trigger Face ID, Touch ID, Windows Hello, a nearby phone, or a hardware security key directly from this button. No identifier field is required in the default discoverable flow.
    </p>

    <div class="mt-5 flex flex-wrap gap-2 text-xs font-medium text-slate-600">
        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">Face ID</span>
        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">Touch ID</span>
        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">Windows Hello</span>
        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">Phone via hybrid</span>
        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">USB / NFC / BLE key</span>
    </div>

    <div class="mt-6">
        <x-vaultic::passkey-button size="sm" label="Continue with passkey" />
    </div>
</div>
