<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use App\Services\SpecManager;
use App\Services\SegmentManager;
//
use App\Services\ModuleReporter;
use App\Services\MPDCache;

class ManifestDetails extends Component
{
    /**
     * @var array<string> $sections;
     **/
    public array $sections;

    public mixed $table;

    public function __construct()
    {
        $this->refresh();
    }


    #[On('spec-selection-changed')]
    #[On('mpd-selected')]
    public function refresh(): void
    {
        $this->sections = ['MPD', 'Segments', 'CrossValidation'];
        $this->reloadTable();
    }

    public function reloadTable(): void
    {
        $specManager = app(SpecManager::class);
        $specManager->validate();
        $segmentManager = new SegmentManager();
        $specManager->validateSegments();

        $this->table = app(ModuleReporter::class)->asTable();//serialize(true);
    }

    public function isOpenSection(string $spec): bool
    {
        return $this->sections[0] == $spec;
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
}
