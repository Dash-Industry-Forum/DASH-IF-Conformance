<?php

namespace App\Modules;

use App\Services\ModuleLogger;
use App\Services\MPDCache;
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

        $mpdCache = app(MPDCache::class);


        $mpdProfiles = explode(',', $mpdCache->getAttribute('profiles'));

        if (in_array('urn:mpeg:dash:profile:isoff-on-demand:2011', $mpdProfiles)) {
            $this->validateOnDemand();
        } else {
            Log::info("Unhandled mpd profile " . implode(',', $mpdProfiles));
        }
    }


    private function validateOnDemand(): void
    {
        $logger = app(ModuleLogger::class);
        $mpdCache = app(MPDCache::class);


        $mpdProfiles = explode(',', $mpdCache->getAttribute('profiles'));
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $logger->validatorMessage($representation->path() . ": "
                        . $representation->getTransientAttribute('profiles'));
                }
            }
        }
    }
}
