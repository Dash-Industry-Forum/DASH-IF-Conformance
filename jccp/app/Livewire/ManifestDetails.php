<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\MPDHandler;
use App\Services\ModuleLogger;

class ManifestDetails extends Component
{
    protected $listeners = [
        'select-mpd' => '$refresh'
    ];

    public function render()
    {
        return view('livewire.manifest-details');
    }

    public function getManifestChecks(): string
    {
        $mpdHandler  = app(MPDHandler::class);
        return $mpdHandler->getResolved();
    }

    public function segmentUrls()
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
