<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AudioChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));
    }

    //Public validation functions
    public function validateAudio(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            $this->validateRoles($period);
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                if ($adaptationSet->getAttribute('contentType') != 'audio') {
                    continue;
                }
                $this->validateFontProperties($adaptationSet);
                $this->validateDolbyChannelConfiguration($adaptationSet);
                //NOTE: Removed DTS as they have been moved to a different spec
            }
        }
    }

    //Private helper functions
    private function validateFontProperties(AdaptationSet $adaptationSet): void
    {
        $hasDownloadableFont = false;
        foreach ($adaptationSet->getDOMElements('SupplementalProperty') as $propertyElement) {
            if ($this->isFontProperty($propertyElement)) {
                $hasDownloadableFont = true;
            }
        }
        foreach ($adaptationSet->getDOMElements('EssentialProperty') as $propertyElement) {
            if ($this->isFontProperty($propertyElement)) {
                $hasDownloadableFont = true;
            }
        }
        $this->v141reporter->test(
            section: "Section 7.2.1.1",
            test: "A fontdownload descriptor SHALL only be placed in AdaptationSets containing subtitles",
            result: !$hasDownloadableFont,
            severity: "FAIL",
            pass_message: "No downloadable fonts found for AdaptationSet " . $adaptationSet->path(),
            fail_message: "At least one downloadable font found for AdaptationSet " . $adaptationSet->path(),
        );
    }

    private function isFontProperty(\DOMElement $propertyElement): bool
    {
        return $propertyElement->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
               $propertyElement->getAttribute('value') == '1';
    }

    private function validateDolbyChannelConfiguration(AdaptationSet $adaptationSet): void
    {
        $codecs = explode(',', $adaptationSet->getAttribute('codecs'));
        foreach ($codecs as $codec) {
            if (!str_starts_with('ec-3', $codec) && !str_starts_with('ac-4', $codec)) {
                return;
            }
        }
        foreach ($adaptationSet->getDOMElements('AudioChannelConfiguration') as $configurationIndex => $configuration) {
            $correctScheme = $configuration->getAttribute('schemeIdUri') ==
                             'tag:dolby.com,2014:dash:audio_channel_configuration:2011';
            $this->v141reporter->test(
                section: "Section 6.3.1",
                test: "For E-AC-3 and AC-4 part 1, the Audio Channel Configuration shall use the " .
                      "'tag:dolby.com,2014:dash:audio_channel_configuration:2011' scheme URI",
                result: $correctScheme,
                severity: "FAIL",
                pass_message: "Configuration scheme at $configurationIndex correct for AdaptationSet " .
                              $adaptationSet->path(),
                fail_message: "Configuration scheme at $configurationIndex incorrect for AdaptationSet " .
                              $adaptationSet->path(),
            );


            $value = $configuration->getAttribute('value');
            $correctValue = strlen($value) == 4 && ctype_xdigit($value);
            $this->v141reporter->test(
                section: "Section 6.3.1",
                test: "[For E-AC-3 and AC-4 part 1], the Audio Channel Configuration value SHALL " .
                      "contain a 4-byte hexadecimal [value]",
                result: $correctValue,
                severity: "FAIL",
                pass_message: "Configuration value at $configurationIndex correct for AdaptationSet " .
                              $adaptationSet->path(),
                fail_message: "Configuration value at $configurationIndex incorrect for AdaptationSet " .
                              $adaptationSet->path(),
            );
        }
    }

    private function validateRoles(Period $period): void
    {
        $hasMainAudio = false;
        $audioAdaptationCount = 0;
        foreach ($period->allAdaptationSets() as $adaptationSet) {
            if ($adaptationSet->getAttribute('contentType') != 'audio') {
                continue;
            }
            $audioAdaptationCount++;
            $roles = $adaptationSet->getDOMElements('Role');

            $atLeastOneDashRole = false;

            foreach ($roles as $role) {
                if ($role->getAttribute('schemeIdUri') != 'urn:mpeg:dash:role:2011') {
                    continue;
                }
                $atLeastOneDashRole = true;
                if ($role->getAttribute('value') == 'main') {
                    $hasMainAudio = true;
                }
            }

            $this->v141reporter->test(
                section: "Section 6.1.2",
                test: "Each audio AdaptationSet shall include at least one Role Element",
                result: $atLeastOneDashRole,
                severity: "FAIL",
                pass_message: "At least one Role found for AdaptationSet " .
                              $adaptationSet->path(),
                fail_message: "No Roles found for AdaptationSet " .
                              $adaptationSet->path(),
            );
        }

        if (!$audioAdaptationCount) {
            return;
        }
        $this->v141reporter->test(
            section: "Section 6.1.2",
            test: "If there is more than one audio Adaptation Set [..] then at least one of them shall be tagged " .
                  "with an @value set to 'main'",
            result: $hasMainAudio || $audioAdaptationCount < 2,
            severity: "FAIL",
            pass_message: "Valid for Period " . $period->path(),
            fail_message: "Invalid for Period " . $period->path(),
        );
    }
}
