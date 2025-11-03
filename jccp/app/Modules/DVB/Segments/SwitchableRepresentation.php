<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use App\Services\SegmentManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SwitchableRepresentation
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $entryTypeCase;
    private TestCase $trackIdCase;
    private TestCase $keyIdCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "A DVB",
            "v.1.4.1",
            []
        ));

        $this->entryTypeCase = $this->v141Reporter->add(
            section: '4.3',
            test: "Initialization segment for Representations with an Adaptation Set SHALL have the same " .
                  "sample entry type",
            skipReason: 'No adaptationset(s) found'
        );
        $this->trackIdCase = $this->v141Reporter->add(
            section: '4.3',
            test: "Initialization segment for Representations with an Adaptation Set SHALL have the same " .
                  "track_ID",
            skipReason: 'No adaptationset(s) found'
        );
        $this->keyIdCase = $this->v141Reporter->add(
            section: '8.3',
            test: "Initialization segment for Representations with an Adaptation Set SHALL have the same " .
                  "default_KID",
            skipReason: 'No encrypted representation(s) found'
        );
    }

    //Public validation functions
    public function validateSwitchableRepresentations(AdaptationSet $adaptationSet): void
    {
        $this->validateGeneric($adaptationSet);
        $this->validateVideo($adaptationSet);
        $this->validateAudio($adaptationSet);
    }

    //Private Helper Functions
    public function validateVideo(AdaptationSet $adaptationSet): void
    {
        //NOTE: Removed some incorrect player video requirements in this commit, do these need to be re-implemented?
    }

    public function validateAudio(AdaptationSet $adaptationSet): void
    {
        //TODO: Re-implement 2.0/5.1 configuration check from this commit
        //NOTE: Removed no-longer accurate DTS Frame duration check in this commit
    }

    public function validateGeneric(AdaptationSet $adaptationSet): void
    {
        //TODO: "Validate 'common' initialization for avc1 or avc2 streams - Section 4.3
        //NOTE: Removed HD/SD check - section 8.3 - due to license server requirements
        $segmentManager = app(SegmentManager::class);

        $sampleEntries = [];
        $trackIds = [];

        $anyProtection = false;
        $keyIds = [];

        $representationCount = 0;

        foreach ($adaptationSet->allRepresentations() as $representation) {
            $representationCount++;
            $segmentList = $segmentManager->representationSegments($representation);
            if (count($segmentList)) {
                $sampleEntries[] = $segmentList[0]->getSampleDescriptor();
                $trackIds[] = $segmentList[0]->getTrackId();

                $protection = $segmentList[0]->getProtectionScheme();
                if ($protection) {
                    $anyProtection = true;
                    $keyIds[] = $protection->encryption->kid;
                }
            }
        }

        $this->entryTypeCase->pathAdd(
            result: count(array_unique($sampleEntries)) <= 1,
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "Unique sample entry type found",
            fail_message: "At least 2 different types found"
        );
        $this->trackIdCase->pathAdd(
            result: count(array_unique($trackIds)) <= 1,
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "Unique track ID found",
            fail_message: "At least 2 different track IDs found"
        );
        if ($anyProtection) {
            $this->keyIdCase->pathAdd(
                result: count(array_unique($keyIds)) == 1 && count($keyIds) == $representationCount,
                severity: "FAIL",
                path: $adaptationSet->path(),
                pass_message: "Unique key ID found",
                fail_message: "At least 2 different key IDs found"
            );
        }
    }
}
