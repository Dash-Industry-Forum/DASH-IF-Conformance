<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\MPDHandler;
use App\Services\ModuleLogger;
use Illuminate\View\View;

class ManifestDetails extends Component
{
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
        $mpdHandler  = app(MPDHandler::class);
        return $mpdHandler->getResolved();
    }

    public function getFeatures(): mixed
    {
        $mpdHandler = app(MPDHandler::class);
        return $mpdHandler->getFeatures();
    }

    public function segmentUrls(): mixed
    {
        $mpdHandler = app(MPDHandler::class);
        return $mpdHandler->internalSegmentUrls();
    }

    public function logs(): string
    {
        $logger = app(ModuleLogger::class);
        return $logger->asJSON();
    }
}
