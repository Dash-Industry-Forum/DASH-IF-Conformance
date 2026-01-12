<?php

namespace App\Modules\IOP\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CrossAdaptation
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $levelCase;
    private TestCase $profileCase;
    private TestCase $editListCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "DASH-IF IOP",
            "4.3",
            []
        ));

        $this->levelCase = $this->legacyReporter->add(
            section: '6.2.5.2',
            test: "The level of the adaptation set SHALL match the maximum of the corresponding representations",
            skipReason: "@bitstreamSwitching not set, or no supported video track found",
        );
        $this->profileCase = $this->legacyReporter->add(
            section: '6.2.5.2',
            test: "The profile of the adaptation set SHALL match the maximum of the corresponding representations",
            skipReason: "@bitstreamSwitching not set, or no supported video track found",
        );
        $this->editListCase = $this->legacyReporter->add(
            section: '6.2.5.2',
            test: "The edit list, if present in one, SHALL be present in all representations",
            skipReason: "@bitstreamSwitching not set, or no supported video track found",
        );
        //TODO: Identical ELST checks
    }

    //Public validation functions
    public function validateCrossAdaptation(AdaptationSet $adaptationSet): void
    {
        if ($adaptationSet->getTransientAttribute('bitstreamSwitching') == '') {
            //return;
        }

        $videoLevels = [];
        $videoProfiles = [];
        $editLists = [];

        $segmentManager = app(SegmentManager::class);

        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);
            if (count($segmentList) < 1) {
                continue;
            }
            $codecs = $representation->getTransientAttribute('codecs');

            if (strpos($codecs, 'avc') !== false) {
                $avcc = $segmentList[0]->getAVCConfiguration();
                $videoLevels[] = intval($avcc['level_idc']);
                $videoProfiles[] = intval($avcc['profile_idc']);

                $elst = $segmentList[0]->boxAccess()->elst();
                if (count($elst)){
                    $editLists[] = $elst;
                }
            }

            if (strpos($codecs, 'hvc') !== false || strpos($codecs, 'hev') !== false) {
                $hvcc = $segmentList[0]->getHEVCConfiguration();
                $videoLevels[] = intval($hvcc['level_idc']);
                $videoProfiles[] = intval($hvcc['profile_idc']);

                $elst = $segmentList[0]->boxAccess()->elst();
                if (count($elst)){
                    $editLists[] = $elst;
                }
            }
        }

        if (count($videoLevels) < 1 || count($videoProfiles) < 1) {
            return;
        }

        $maxLevel = max($videoLevels);
        $maxProfile = max($videoProfiles);

        $adaptationCodecs = explode(',', $adaptationSet->getTransientAttribute('codecs'));

        foreach ($adaptationCodecs as $codec) {
            $adaptLevel = '';
            $adaptProfile = '';
            if (strpos($codec, 'avc') !== false) {
                $adaptLevel = substr($codec, 9, 2);
                $adaptProfile = substr($codec, 5, 2);
            }
            if (strpos($codec, 'hvc') !== false || strpos($codec, 'hev') !== false) {
                $parts = explode('.', $codec);
                $adaptLevel = $parts[3];
                $adaptProfile = $parts[1];
            }

            $this->levelCase->pathAdd(
                path: $adaptationSet->path(),
                result: ("L" . $maxLevel) == $adaptLevel,
                severity: "FAIL",
                pass_message: "Level matches",
                fail_message: "Level does not match for '$codec', $adaptLevel != L" . $maxLevel,
            );
            $this->profileCase->pathAdd(
                path: $adaptationSet->path(),
                result: dechex($maxProfile) == $adaptProfile,
                severity: "FAIL",
                pass_message: "Profile matches",
                fail_message: "Profile does not match for '$codec', $adaptProfile != " . dechex($maxProfile)
            );
        }


        $this->editListCase->pathAdd(
            path: $adaptationSet->path(),
            result: count($editLists) == 0 || count($editLists) == count($videoProfiles),
            severity: "FAIL",
            pass_message: "Edit list not present, or present in all supported video tracks",
            fail_message: "Edit list present, but missing from at least one supported video track",
        );


    }
}
