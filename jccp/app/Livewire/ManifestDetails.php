<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;
//
use App\Services\Segment;

class ManifestDetails extends Component
{
    private array $sections = ['MPD', 'Segments'];

    /**
     * @var array<Segment> $egmentDebug;
     **/
    private array $segmentDebug;

    public function __construct()
    {
    }

    /**
     * This is a laravel-specific type, so we ignore it
     * @phpstan-ignore missingType.property
     **/
    protected $listeners = [
        'select-mpd' => '$refresh'
    ];

    public function selectSpec(string $spec): void
    {
        $this->selectedSpec = $spec;
    }

    public function render(): View
    {
        return view('livewire.manifest-details');
    }






    public function getFeatures(): mixed
    {
        return \json_encode($this->segmentDebug, JSON_PRETTY_PRINT);
    }


}
