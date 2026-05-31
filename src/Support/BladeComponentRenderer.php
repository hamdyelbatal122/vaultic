<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Support;

use Illuminate\Support\HtmlString;

/**
 * Helper to render Blade component views as HtmlString instances.
 *
 * Used internally by the package's helper functions and Blade directives.
 */
class BladeComponentRenderer
{
    /**
     * Render a Blade view and return it as an HtmlString.
     *
     * @param  string                $view
     * @param  array<string, mixed>  $data
     * @return HtmlString
     */
    public static function render(string $view, array $data = []): HtmlString
    {
        return new HtmlString(view($view, $data)->render());
    }
}