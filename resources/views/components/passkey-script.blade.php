@once
    <script>
        (() => {
            if (window.__vaulticPasskeyInitialized) {
                return;
            }

            window.__vaulticPasskeyInitialized = true;

            const textEncoder = new TextEncoder();

            const setStatus = (button, message, state = 'idle') => {
                const container = button.closest('div');
                const status = container ? container.querySelector('[data-vaultic-passkey-status]') : null;

                if (!status) {
                    return;
                }

                status.textContent = message || '';
                status.dataset.state = state;
                status.classList.remove('text-slate-500', 'text-emerald-700', 'text-rose-600');

                if (state === 'success') {
                    status.classList.add('text-emerald-700');
                    return;
                }

                if (state === 'error') {
                    status.classList.add('text-rose-600');
                    return;
                }

                status.classList.add('text-slate-500');
            };

            const dispatchVaulticEvent = (name, detail) => {
                window.dispatchEvent(new CustomEvent(name, { detail }));
            };

            const decodeCredentialValue = (value) => {
                const normalizedValue = String(value || '');

                if (/^[a-f0-9]+$/i.test(normalizedValue) && normalizedValue.length % 2 === 0) {
                    const bytes = new Uint8Array(normalizedValue.length / 2);

                    for (let index = 0; index < normalizedValue.length; index += 2) {
                        bytes[index / 2] = Number.parseInt(normalizedValue.slice(index, index + 2), 16);
                    }

                    return bytes;
                }

                try {
                    const base64 = normalizedValue.replace(/-/g, '+').replace(/_/g, '/');
                    const padded = base64 + '='.repeat((4 - (base64.length % 4 || 4)) % 4);
                    const binary = window.atob(padded);
                    const bytes = new Uint8Array(binary.length);

                    for (let index = 0; index < binary.length; index += 1) {
                        bytes[index] = binary.charCodeAt(index);
                    }

                    return bytes;
                } catch (error) {
                    return textEncoder.encode(normalizedValue);
                }
            };

            const encodeBase64Url = (value) => {
                const bytes = value instanceof ArrayBuffer ? new Uint8Array(value) : new Uint8Array(value.buffer ?? value);
                let binary = '';

                bytes.forEach((byte) => {
                    binary += String.fromCharCode(byte);
                });

                return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
            };

            const publicKeyToCreationOptions = (options) => ({
                ...options,
                challenge: decodeCredentialValue(options.challenge),
                user: {
                    ...options.user,
                    id: textEncoder.encode(String(options.user.id)),
                },
                excludeCredentials: (options.excludeCredentials || []).map((credential) => ({
                    ...credential,
                    id: decodeCredentialValue(credential.id),
                })),
            });

            const publicKeyToRequestOptions = (options) => ({
                ...options,
                challenge: decodeCredentialValue(options.challenge),
                allowCredentials: (options.allowCredentials || []).map((credential) => ({
                    ...credential,
                    id: decodeCredentialValue(credential.id),
                })),
            });

            const credentialToJson = (credential) => {
                const response = credential.response || {};

                return {
                    id: credential.id,
                    type: credential.type,
                    rawId: encodeBase64Url(credential.rawId),
                    response: {
                        clientDataJSON: response.clientDataJSON ? encodeBase64Url(response.clientDataJSON) : null,
                        attestationObject: response.attestationObject ? encodeBase64Url(response.attestationObject) : null,
                        authenticatorData: response.authenticatorData ? encodeBase64Url(response.authenticatorData) : null,
                        signature: response.signature ? encodeBase64Url(response.signature) : null,
                        userHandle: response.userHandle ? encodeBase64Url(response.userHandle) : null,
                    },
                    clientExtensionResults: typeof credential.getClientExtensionResults === 'function'
                        ? credential.getClientExtensionResults()
                        : {},
                };
            };

            const resolveSelectorValue = (button, selector) => {
                if (!selector) {
                    return null;
                }

                const field = document.querySelector(selector);

                if (!field) {
                    return null;
                }

                return 'value' in field ? field.value : field.textContent;
            };

            const postJson = async (url, payload, csrfToken) => {
                const response = await window.fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data.message || 'Passkey request failed.';
                    const error = new Error(message);
                    error.payload = data;
                    throw error;
                }

                return data;
            };

            const requireCapability = (button) => {
                if (typeof window.PublicKeyCredential === 'undefined' || !navigator.credentials) {
                    button.disabled = true;
                    setStatus(button, 'This browser does not support passkeys.', 'error');
                    dispatchVaulticEvent('vaultic:unsupported', { button });

                    return false;
                }

                return true;
            };

            const setBusy = (button, busy) => {
                button.disabled = busy;
                button.dataset.busy = busy ? 'true' : 'false';
            };

            const handleSuccess = (button, payload, action) => {
                const successMessage = payload.message || (action === 'register'
                    ? 'Passkey registered successfully.'
                    : 'Authenticated with passkey.');

                setStatus(button, successMessage, 'success');
                dispatchVaulticEvent('vaultic:success', { action, payload, button });

                if (action === 'login') {
                    const redirectTo = button.dataset.redirectTo || payload.redirect_to;

                    if (redirectTo) {
                        window.location.assign(redirectTo);
                        return;
                    }
                }

                if (button.dataset.reloadOnSuccess === 'true') {
                    window.location.reload();
                }
            };

            const handleError = (button, error, action) => {
                const payload = error.payload || {};
                const message = payload.message || error.message || 'Passkey flow failed.';

                setStatus(button, message, 'error');
                dispatchVaulticEvent('vaultic:error', { action, payload, button, error });
            };

            const resolveIdentifier = (button) => button.dataset.identifier || resolveSelectorValue(button, button.dataset.identifierSelector);
            const resolveDeviceName = (button) => button.dataset.name || resolveSelectorValue(button, button.dataset.nameSelector);

            const runLogin = async (button) => {
                if (!button.dataset.optionsUrl || !button.dataset.submitUrl || button.dataset.optionsUrl === '#' || button.dataset.submitUrl === '#') {
                    throw new Error('Vaultic passkey routes are not available in this application.');
                }

                const identifier = (resolveIdentifier(button) || '').trim();

                if (!identifier) {
                    throw new Error('Enter your account identifier before using a passkey.');
                }

                const guard = button.dataset.guard || null;
                const optionsPayload = { identifier };

                if (guard) {
                    optionsPayload.guard = guard;
                }

                setStatus(button, 'Looking for available passkeys...', 'idle');
                const options = await postJson(button.dataset.optionsUrl, optionsPayload, button.dataset.csrfToken);
                const credential = await navigator.credentials.get({ publicKey: publicKeyToRequestOptions(options) });

                if (!credential) {
                    throw new Error('No passkey was selected.');
                }

                const submitPayload = {
                    ...credentialToJson(credential),
                    identifier,
                };

                if (guard) {
                    submitPayload.guard = guard;
                }

                setStatus(button, 'Verifying your passkey...', 'idle');
                const result = await postJson(button.dataset.submitUrl, submitPayload, button.dataset.csrfToken);
                handleSuccess(button, result, 'login');
            };

            const runRegistration = async (button) => {
                if (!button.dataset.optionsUrl || !button.dataset.submitUrl || button.dataset.optionsUrl === '#' || button.dataset.submitUrl === '#') {
                    throw new Error('Vaultic passkey routes are not available in this application.');
                }

                const guard = button.dataset.guard || null;
                const optionsPayload = guard ? { guard } : {};

                setStatus(button, 'Preparing a new passkey registration...', 'idle');
                const options = await postJson(button.dataset.optionsUrl, optionsPayload, button.dataset.csrfToken);
                const credential = await navigator.credentials.create({ publicKey: publicKeyToCreationOptions(options) });

                if (!credential) {
                    throw new Error('Passkey registration was cancelled.');
                }

                const submitPayload = credentialToJson(credential);
                const name = (resolveDeviceName(button) || '').trim();

                if (name) {
                    submitPayload.name = name;
                }

                if (guard) {
                    submitPayload.guard = guard;
                }

                setStatus(button, 'Saving your new passkey...', 'idle');
                const result = await postJson(button.dataset.submitUrl, submitPayload, button.dataset.csrfToken);
                handleSuccess(button, result, 'register');
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-vaultic-passkey]');

                if (!button) {
                    return;
                }

                event.preventDefault();

                if (!requireCapability(button)) {
                    return;
                }

                const resolvedAction = button.dataset.vaulticAction || button.getAttribute('data-vaultic-action') || 'login';

                try {
                    setBusy(button, true);

                    if (resolvedAction === 'register') {
                        await runRegistration(button);
                    } else {
                        await runLogin(button);
                    }
                } catch (error) {
                    handleError(button, error, resolvedAction);
                } finally {
                    setBusy(button, false);
                }
            });
        })();
    </script>
@endonce