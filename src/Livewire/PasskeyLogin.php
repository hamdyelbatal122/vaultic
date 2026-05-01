<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Livewire;

use Livewire\Component;

final class PasskeyLogin extends Component
{
    public string $identifier = '';

    public function render()
    {
        return view('vaultic::components.login');
    }
}
