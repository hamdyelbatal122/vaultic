<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Livewire;

use Livewire\Component;

/**
 * Livewire component for the passkey login flow.
 */
class PasskeyLogin extends Component
{
    /** @var string */
    public $identifier = '';

    public function render()
    {
        return view('vaultic::components.login');
    }
}
