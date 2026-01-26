<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\View;
use App\Services\SegmentManager;

class SegmentQueue extends Component
{
    private SegmentManager $segmentManager;

    public function __construct()
    {
        $this->segmentManager = app(SegmentManager::class);
    }
    public function render(): View
    {
        return view('livewire.segment-queue');
    }

    public function isLoading(): int
    {
        $segmentManager = app(SegmentManager::class);
        return $segmentManager->segmentCount() - $segmentManager->queuedStatus();
    }

    public function segmentCount(): int
    {
        return app(SegmentManager::class)->segmentCount();
    }

    public function stateCount(string $state): int
    {
        return $this->segmentManager->segmentState()[$state];
    }


    /**
     * @return array<string,string>
     **/
    public function failedSegments(): array
    {
        return $this->segmentManager->failedSegments();
    }
}
