<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SpecManager;
use App\Services\ModuleReporter;

class MpdResults extends Component
{
    private string $selectedSpec = '';

    public function __construct()
    {

        $specManager = app(SpecManager::class);
        $specManager->enable('HbbTV MPD Module');
        $specManager->enable('DVB Segments Module');
        //$specManager->enable('Wave HLS Interop Segments Module');
        $specManager->validate();

        $this->results = app(ModuleReporter::class)->serialize(true);
        if ($this->selectedSpec == '') {
            $allSpecs = $this->getSpecs();
            if (count($allSpecs) > 0) {
                $this->selectSpec($allSpecs[0]);
            }
        }
    }

    public function render()
    {
        return view('livewire.mpd-results');
    }

    /**
     * @return array<string>
     **/
    public function getSpecs(): array
    {
        $keys = [];
        if (array_key_exists("MPD", $this->results)) {
            $keys = array_merge(
                $keys,
                array_keys($this->results["MPD"]),
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

    /**
     * @return array<array<string, mixed>>
     **/
    public function transformResults(string $spec): array
    {
        $res = [];
        if (!$spec) {
            return $res;
        }
        if (!array_key_exists("MPD", $this->results)) {
            return $res;
        }

        if (!array_key_exists($spec, $this->results["MPD"])) {
            return $res;
        }

        foreach ($this->results["MPD"][$spec] as $section => $sectionResults) {
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
}
