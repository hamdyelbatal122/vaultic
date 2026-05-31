<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Livewire;

use Livewire\Component;

/**
 * Livewire component for the passkey registration flow.
 */
class PasskeyRegister extends Component
{
    /** @var string */
    public $name = '';

    public function render()
    {
        return view('vaultic::components.register');
    }
}
