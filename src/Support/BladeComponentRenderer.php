<?php

namespace Hamzi\Vaultic\Support;

use Illuminate\Support\HtmlString;

class BladeComponentRenderer
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): HtmlString
    {
        return new HtmlString(view($view, $data)->render());
    }
}