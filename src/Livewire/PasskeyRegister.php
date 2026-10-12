<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Livewire component for the passkey registration flow.
 */
class PasskeyRegister extends Component
{
    /** @var string */
    public $name = '';

    public function render(): View
    {
        return view('vaultic::components.register');
    }
}
