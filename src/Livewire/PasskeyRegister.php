<?php

namespace Hamzi\Vaultic\Livewire;

use Livewire\Component;

class PasskeyRegister extends Component
{
    public $name = '';

    public function render()
    {
        return view('vaultic::components.register');
    }
}
