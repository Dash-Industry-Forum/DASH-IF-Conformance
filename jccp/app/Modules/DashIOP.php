<?php

namespace App\Modules;

use App\Services\ModuleLogger;
use App\Services\MPDHandler;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;

class DashIOP extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF IOP Conformance";
    }

    public function MPDHook(): void
    {
        parent::MPDHook();
        $logger = app(ModuleLogger::class);

        $mpdHandler = app(MPDHandler::class);

        $mpdProfiles = $mpdHandler->getMPDProfiles();
        foreach ($mpdHandler->getMPDProfiles() as $mpdProfile) {
            $logger->validatorMessage($mpdProfile);
        }

        if (in_array('urn:mpeg:dash:profile:isoff-on-demand:2011', $mpdProfiles)) {
            $this->validateOnDemand();
        } else {
            Log::info("Unhandled mpd profile " . implode(',', $mpdProfiles));
        }
    }


    private function validateOnDemand(): void
    {
        $logger = app(ModuleLogger::class);
        $mpdHandler = app(MPDHandler::class);

        $mpdProfiles = $mpdHandler->getMPDProfiles();
        foreach ($mpdHandler->getPeriods() as $periodIdx => $period) {
            $periodProfiles = $period->getProfiles($mpdProfiles);
            foreach ($period->getAdaptationSets() as $adaptationSetIdx => $adaptationSet) {
                $adaptationProfiles = $adaptationSet->getProfiles($periodProfiles);
                foreach ($adaptationSet->getRepresentations() as $representationIdx => $representation) {
                    $representationProfiles = $representation->getProfiles($adaptationProfiles);
                    $logger->validatorMessage(
                        "$periodIdx::$adaptationSetIdx::$representationIdx" . implode(',', $representationProfiles)
                    );
                }
            }
        }
    }
}
