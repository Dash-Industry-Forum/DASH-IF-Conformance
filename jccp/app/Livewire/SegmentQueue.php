<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\View;
use App\Services\SegmentManager;

class SegmentQueue extends Component
{
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
}
