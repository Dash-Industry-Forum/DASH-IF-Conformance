<?php

namespace App\Livewire;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
//
use Livewire\Component;
use App\Services\SegmentManager;

class SpecManager extends Component
{
    public function render(): View
    {
        return view('livewire.spec-manager');
    }

    public function specManagerState(): string
    {
        return app(\App\Services\SpecManager::class)->stateJSON();
    }

    /**
     * @return array<string>
     **/
    public function mpdSpecs(): array
    {
        return array_filter(app(\App\Services\SpecManager::class)->specNames(), function ($spec) {
            return strpos($spec, "MPD") !== false;
        });
    }
    /**
     * @return array<string>
     **/
    public function segmentSpecs(): array
    {
        return array_filter(app(\App\Services\SpecManager::class)->specNames(), function ($spec) {
            return strpos($spec, "Segment") !== false;
        });
    }

    public function buttonClassForSpec(string $spec): string
    {
        $state = app(\App\Services\SpecManager::class)->specState($spec);
        if ($state == 'Enabled') {
            return 'btn-success';
        }
        if ($state == 'Dependency') {
            return 'btn-outline-success';
        }
        return 'btn-outline-dark';
    }

    public function enable(string $spec): void
    {
        app(\App\Services\SpecManager::class)->toggle($spec);
        $this->dispatch('spec-selection-changed');
    }

    public function isDisabled(string $spec): bool
    {
        if ($spec == "Global Module") {
            return true;
        }
        if (strpos($spec, "MPD") !== false) {
            return false;
        }
        return $this->segmentsLoading();
    }

    public function segmentsLoading(): bool
    {
        $segmentManager = app(SegmentManager::class);
        return $segmentManager->queuedStatus() > 0;
    }
}
