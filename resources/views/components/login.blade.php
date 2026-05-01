<div class="mx-auto w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-900">Sign in with passkey</h2>
    <p class="mt-1 text-sm text-slate-600">Use any registered device: phone, laptop, or security key.</p>

    <div class="mt-4">
        <label class="mb-2 block text-sm font-medium text-slate-700" for="identifier">Email</label>
        <input id="identifier" type="email" wire:model="identifier" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="you@example.com" />
    </div>

    <button type="button" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" data-passkeys-login>
        Continue with Passkey
    </button>
</div>
