<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\MPDCache;
use App\Services\ModuleLogger;
use App\Modules\DashIOP;
use Illuminate\View\View;

class ManifestDetails extends Component
{
    //TODO re-implement
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

    public function getManifestChecks(): string
    {
        return '';
    }

    public function getFeatures(): mixed
    {
        return null;
    }

    public function segmentUrls(): mixed
    {
        return null;
    }

    public function logs(): string
    {
        return '';
    }
}
