<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\MPDCache;
use App\Services\SpecManager;
use App\Services\Downloader;
use App\Services\ModuleReporter;
use App\Modules\DashIOP;
use App\Modules\DVB\MPD as DVBMpd;
//
use App\Services\Segment;

class ManifestDetails extends Component
{
    private array $sections = ['MPD', 'Segments'];

    /**
     * @var array<Segment> $segmentDebug;
     **/
    private array $segmentDebug;

    public function __construct()
    {

        $specManager = app(SpecManager::class);
        $specManager->enable('HbbTV MPD Module');
        $specManager->enable('DVB Segments Module');
        //$specManager->enable('Wave HLS Interop Segments Module');
        $specManager->validate();


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



    public function specManagerState(): string
    {
        return app(SpecManager::class)->stateJSON();
    }



    public function getFeatures(): mixed
    {
        return \json_encode($this->segmentDebug, JSON_PRETTY_PRINT);
    }


}
