<?php

namespace Hamzi\Vaultic\Livewire;

use Livewire\Component;

class PasskeyLogin extends Component
{
    public $identifier = '';

    public function render()
    {
        return view('vaultic::components.login');
    }
}
