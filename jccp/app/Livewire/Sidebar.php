<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public function render(): View
    {
        return view('livewire.sidebar');
    }
}
