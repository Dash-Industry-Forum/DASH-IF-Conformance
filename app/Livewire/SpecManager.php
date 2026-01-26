<?php

namespace App\Livewire;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
//
use Livewire\Component;
use App\Services\SegmentManager;

class SpecManager extends Component
{
    private \App\Services\SpecManager $specManager;

    public function __construct()
    {
        $this->specManager = app(\App\Services\SpecManager::class);
    }

    public function render(): View
    {
        return view('livewire.spec-manager');
    }

    public function specManagerState(): string
    {
        return $this->specManager->stateJSON();
    }

    /**
     * @return array<string>
     **/
    public function mpdSpecs(): array
    {
        $manifestSpecifications = array_filter($this->specManager->specNames(), function ($spec) {
            return strpos($spec, "MPD") !== false;
        });
        return array_map(
            function ($spec) {
                return substr($spec, 0, strpos($spec, " MPD"));
            },
            $manifestSpecifications
        );
    }
    /**
     * @return array<string>
     **/
    public function segmentSpecs(): array
    {
        $segmentSpecifications = array_filter($this->specManager->specNames(), function ($spec) {
            return strpos($spec, "Segment") !== false;
        });
        return array_map(
            function ($spec) {
                return substr($spec, 0, strpos($spec, " Segment"));
            },
            $segmentSpecifications
        );
    }

    public function buttonClassForSpec(string $spec, string $type): string
    {
        $state = $this->specManager->specState("$spec $type");
        if ($state == 'Enabled') {
            return 'btn-success';
        }
        if ($state == 'Dependency') {
            return 'btn-success';
        }
        return 'btn-outline-dark';
    }

    public function enable(string $spec, string $type): void
    {
        $this->specManager->toggle("$spec $type");
        $this->dispatch('spec-selection-changed');
    }

    public function isDisabled(string $spec, string $type): bool
    {
        if ($spec == "Global Module") {
            return true;
        }
        $state = $this->specManager->specState("$spec $type");
        if ($state == 'Dependency') {
            return true;
        }
        if ($type == "MPD") {
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
