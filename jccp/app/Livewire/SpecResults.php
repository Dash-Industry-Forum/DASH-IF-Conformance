<?php

namespace App\Livewire;

use Livewire\Component;

class SpecResults extends Component
{
    public $specResults;

    public function mount($specResults)
    {
        $this->specResults = $specResults;
    }
    public function render()
    {
        return view('livewire.spec-results');
    }

    public function transformResults(): array
    {
        $res = [];

        foreach ($this->specResults as $section => $sectionResults) {
            foreach ($sectionResults['checks'] as $check => $checkResults) {
                $res[] = [
                    'section' => $section,
                    'check' => $check,
                    'state' => $checkResults['state'],
                    'messages' => implode('<br/>',$checkResults['messages'])
                ];
            }
        }

        return $res;
    }
}
