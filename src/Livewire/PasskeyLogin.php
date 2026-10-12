<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Livewire component for the passkey login flow.
 */
class PasskeyLogin extends Component
{
    /** @var string */
    public $identifier = '';

    public function render(): View
    {
        return view('vaultic::components.login');
    }
}
