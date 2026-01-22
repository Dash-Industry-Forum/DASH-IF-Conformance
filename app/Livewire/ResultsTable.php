<?php

namespace App\Livewire;

use App\Services\SpecManager;
use App\Services\SegmentManager;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\View\View;
use App\Services\ModuleReporter;

class ResultsTable extends Component
{
    public mixed $table;
    private SegmentManager $segmentManager;

    public function __construct()
    {
        $this->segmentManager = app(SegmentManager::class);
        $this->refresh();
    }

    public function stateCount(string $state): int
    {
        return $this->segmentManager->segmentState()[$state];
    }

    /**
     * @return array<string>
     **/
    public function failedSegments(): array
    {

        return $this->segmentManager->failedSegments();
    }


    public function render(): View
    {
        return view('livewire.results-table');
    }

    #[On('spec-selection-changed')]
    public function refresh(): void
    {
        $specManager = app(SpecManager::class);
        $specManager->validate();
        $specManager->validateSegments();

        $this->table = app(ModuleReporter::class)->asTable();//serialize(true);
    }


    public function getResult(string $section, string $element): string
    {
        if (!array_key_exists($section, $this->table)) {
            return '';
        }
        $s = $this->table[$section];
        if (!array_key_exists($element, $s)) {
            return '';
        }
        return $s[$element];
    }

    public function getResultClass(string $section, string $element): string
    {
        $result = $this->getResult($section, $element);
        if ($result == '✗') {
            return 'text-danger';
        }
        if ($result == '!') {
            return 'text-warning';
        }
        if ($result == '✓') {
            return 'text-success';
        }
        return '';
    }
}
