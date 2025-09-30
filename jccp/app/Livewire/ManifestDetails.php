<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;

//

class ManifestDetails extends Component
{
    /**
     * @var array<string> $sections;
     **/
    public array $sections;


    public function __construct()
    {
        $this->refresh();
    }

    #[On('mpd-selected')]
    public function refresh(): void
    {
        $this->sections = ['MPD', 'Segments'];
    }


    /**
     * This is a laravel-specific type, so we ignore it
     * @phpstan-ignore missingType.property
     **/
    protected $listeners = [
        'select-mpd' => '$refresh'
    ];

    public function render(): View
    {
        return view('livewire.manifest-details');
    }
}
