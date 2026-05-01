<div class="mx-auto w-full max-w-xl overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_24px_80px_-32px_rgba(15,23,42,0.35)]">
    <div class="bg-[radial-gradient(circle_at_top_left,_rgba(15,23,42,0.12),_transparent_55%)] px-6 py-6 sm:px-8">
        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
            WebAuthn
        </div>

        <h2 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950">Sign in with your passkey</h2>
        <p class="mt-2 max-w-lg text-sm leading-6 text-slate-600">
            Use Face ID, Touch ID, Windows Hello, your phone, or a hardware security key to complete sign-in without a password.
        </p>

        <div class="mt-6 space-y-3">
            <label class="block text-sm font-medium text-slate-700" for="vaultic-login-identifier">Account identifier</label>
            <input
                id="vaultic-login-identifier"
                type="text"
                name="identifier"
                autocomplete="username webauthn"
                class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-200"
                placeholder="you@example.com"
            />
        </div>

        <div class="mt-6">
            <x-vaultic::passkey-button identifier-selector="#vaultic-login-identifier" class="w-full" />
        </div>
    </div>
</div>
