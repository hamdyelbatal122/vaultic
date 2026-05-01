<?php

use Hamzi\Vaultic\Support\BladeComponentRenderer;
use Illuminate\Support\HtmlString;

if (! function_exists('vaultic_passkey_button')) {
    /**
     * @param array<string, mixed> $data
     */
    function vaultic_passkey_button(array $data = []): HtmlString
    {
        return BladeComponentRenderer::render('vaultic::components.passkey-button', $data);
    }
}

if (! function_exists('vaultic_passkey_panel')) {
    /**
     * @param array<string, mixed> $data
     */
    function vaultic_passkey_panel(array $data = []): HtmlString
    {
        return BladeComponentRenderer::render('vaultic::components.passkey-panel', $data);
    }
}