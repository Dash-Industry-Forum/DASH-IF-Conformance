<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\View;
use App\Services\SpecManager;
use App\Services\ModuleReporter;
use App\Services\SegmentManager;
use App\Services\Segment;

class MpdResults extends Component
{
    private mixed $results;
    private string $selectedSpec = '';
    public string $section;

    /**
     * @var array<Segment> $segmentDebug;
     **/
    private array $segmentDebug;

    public function mount(string $section): void
    {
        $this->section = $section;

        $specManager = app(SpecManager::class);
        if ($this->section == "MPD") {
            $specManager->validate();
        }
        if ($this->section == "Segments") {
            $segmentManager = new SegmentManager();
            $this->segmentDebug = $segmentManager->getSegments(0, 0, 0);
            $specManager->validateSegments();
        }

        $this->results = app(ModuleReporter::class)->serialize(true);
        if ($this->selectedSpec == '') {
            $allSpecs = $this->getSpecs();
            if (count($allSpecs) > 0) {
                $this->selectSpec($allSpecs[0]);
            }
        }
    }

    public function render(): View
    {
        return view('livewire.mpd-results');
    }

    /**
     * @return array<string>
     **/
    public function getSpecs(): array
    {
        $keys = [];
        if (array_key_exists($this->section, $this->results)) {
            $keys = array_merge(
                $keys,
                array_keys($this->results[$this->section]),
            );
        }
        $keys = array_unique($keys);

        sort($keys);
        return $keys;
    }

    public function selectSpec(string $spec): void
    {
        $this->selectedSpec = $spec;
    }

    private function resultsForSpec(string $spec): mixed
    {
        if (!$spec) {
            return null;
        }
        if (!array_key_exists($this->section, $this->results)) {
            return null;
        }
        if (!array_key_exists($spec, $this->results[$this->section])) {
            return null;
        }
        return $this->results[$this->section][$spec];
    }

    public function getSpecResult(string $spec): string
    {
        $specResults = $this->resultsForSpec($spec);
        if (!$specResults) {
            return "";
        }
        $res = "✓";
        foreach ($specResults as $section => $sectionResults) {
            foreach ($sectionResults['checks'] as $check => $checkResults) {
                if ($checkResults['state'] == "FAIL") {
                    return "✗";
                }
                if ($checkResults['state'] == "WARN") {
                    $res =  "!";
                }
            }
        }
        return $res;
    }

    /**
     * @return array<array<string, mixed>>
     **/
    public function transformResults(string $spec): array
    {
        $res = [];
        $specResults = $this->resultsForSpec($spec);
        if (!$specResults) {
            return $res;
        }
        foreach ($specResults as $section => $sectionResults) {
            foreach ($sectionResults['checks'] as $check => $checkResults) {
                $res[] = [
                    'section' => $section,
                    'check' => $check,
                    'state' => $checkResults['state'],
                    'messages' => $checkResults['messages']
                ];
            }
        }

        usort(
            $res,
            function ($lhs, $rhs) {
                if ($lhs['state'] == $rhs['state']) {
                    return 0;
                }
                if ($lhs['state'] == 'FAIL') {
                    return -1;
                }
                if ($rhs['state'] == 'FAIL') {
                    return 1;
                }
                if ($lhs['state'] == 'WARN') {
                    return -1;
                }
                if ($rhs['state'] == 'WARN') {
                    return 1;
                }
                return 0;
            }
        );

        return $res;
    }

    public function getSegmentDebug(): mixed
    {
        return \json_encode($this->segmentDebug, JSON_PRETTY_PRINT);
    }
}
