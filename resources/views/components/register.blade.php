<div class="mx-auto w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-semibold text-slate-900">Register a new passkey</h2>
    <p class="mt-1 text-sm text-slate-600">Add this device as a secure passwordless authenticator.</p>

    <div class="mt-4">
        <label class="mb-2 block text-sm font-medium text-slate-700" for="passkey-name">Device label</label>
        <input id="passkey-name" type="text" wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="My Laptop" />
    </div>

    <button type="button" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" data-passkeys-register>
        Create Passkey
    </button>
</div>
