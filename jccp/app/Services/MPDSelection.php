<?php

namespace App\Services;

use App\Services\ModuleLogger;
use App\Services\Schematron;
use Illuminate\Support\Facades\Log;

class MPDSelection
{
    private int $selectedPeriod = 0;
    private int $selectedAdaptationSet = 0;
    private int $selectedRepresentation = 0;

    public function selectPeriod(int $period): void
    {
        $this->selectedPeriod = $period;
    }
    public function selectNextPeriod(): void
    {
        $this->selectedPeriod++;
    }
    public function getSelectedPeriod(int $override = -1): int
    {
        return $override == -1 ?  $this->selectedPeriod : $override;
    }

    public function selectAdaptationSet(int $adaptationSet): void
    {
        $this->selectedAdaptationSet = $adaptationSet;
    }
    public function selectNextAdaptationSet(): void
    {
        $this->selectedAdaptationSet++;
    }
    public function getSelectedAdaptationSet(int $override = -1): int
    {
        return $override == -1 ? $this->selectedAdaptationSet : $override;
    }

    public function selectRepresentation(int $representation): void
    {
        $this->selectedRepresentation = $representation;
    }
    public function selectNextRepresentation(): void
    {
        $this->selectedRepresentation++;
    }
    public function getSelectedRepresentation(int $override = -1): int
    {
        return  $override == -1 ? $this->selectedRepresentation : $override;
    }
}
