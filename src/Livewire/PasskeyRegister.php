<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Livewire;

use Livewire\Component;

final class PasskeyRegister extends Component
{
    public string $name = '';

    public function render()
    {
        return view('vaultic::components.register');
    }
}
