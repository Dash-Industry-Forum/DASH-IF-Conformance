<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Symfony\Component\HttpFoundation\StreamedResponse;
//
use App\Services\ModuleReporter;
use App\Services\MPDCache;
use App\Services\SpecManager;

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
        $this->sections = ['MPD', 'Segments', 'CrossValidation'];
    }


    public function render(): View
    {
        return view('livewire.manifest-details');
    }

    public function show(): bool
    {
        if (!session()->get('mpd')) {
            return false;
        }
        $mpdCache = app(MPDCache::class);
        $mpdCache->getMPD();
        return $mpdCache->error == '';
    }

    public function download(): StreamedResponse
    {
        $moduleReporter = app(ModuleReporter::class);
        $specManager = app(SpecManager::class);
        $specManager->validate();
        $specManager->validateSegments();
        return response()->streamDownload(function () use ($moduleReporter) {
            echo json_encode($moduleReporter->serialize(true));
        }, 'results.json');
    }
}
