<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AudioChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $fontCase;
    private TestCase $dolbyCase;
    private TestCase $roleCase;
    private TestCase $attributeCase;
    private TestCase $fallbackCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->fontCase = $this->v141reporter->add(
            section: "Section 7.2.1.1",
            test: "A fontdownload descriptor SHALL only be placed in AdaptationSets containing subtitles",
            skipReason: "",
        );
        $this->dolbyCase = $this->v141reporter->add(
            section: "Section 6.3.1",
            test: "The AudioChannelConfiguration shall be compliant with the dolby scheme",
            skipReason: "No E-AC-3 or AC-4 part 1 stream found"
        );
        $this->roleCase = $this->v141reporter->add(
            section: "Section 6.1.2",
            test: "Each audio AdaptationSet shall include at least one Role Element",
            skipReason: "No audio AdaptationSet found"
        );
        $this->attributeCase = $this->v141reporter->add(
            section: "Section 6.1.1",
            test: "All audio Representations SHALL have the attributes and elements in Table 4",
            skipReason: "No audio Representation found"
        );
        $this->fallbackCase = $this->v141reporter->add(
            section: "Section 6.6.3",
            test: "An audio fallback set shall have @value equal to the @id of the base set",
            skipReason: "No audio fallback set found",
        );
    }

    //Public validation functions
    public function validateAudio(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            $audioAdaptationSetById = [];
            $this->validateRoles($period);
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                if ($adaptationSet->getAttribute('contentType') != 'audio') {
                    continue;
                }
                $audioAdaptationSetById[$adaptationSet->getAttribute('id')] = $adaptationSet;

                $this->validateFontProperties($adaptationSet);
                $this->validateAttributes($adaptationSet);

                $this->validateDolbyChannelConfiguration($adaptationSet);
                //NOTE: Removed DTS as they have been moved to a different spec
            }
            $this->validateFallback($audioAdaptationSetById);
        }
    }

    //Private helper functions
    private function validateFontProperties(AdaptationSet $adaptationSet): void
    {
        //TODO Move font checks to validateSubtitles() only!
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
        $this->fontCase->pathAdd(
            path: $adaptationSet->path(),
            result: !$hasDownloadableFont,
            severity: "FAIL",
            pass_message: "No downloadable fonts found",
            fail_message: "At least one downloadable font found",
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

            $this->dolbyCase->pathAdd(
                path: $adaptationSet->path() . "@$configurationIndex",
                result: $correctScheme,
                severity: "FAIL",
                pass_message: "Scheme URI correct",
                fail_message: "Scheme URI incorrect - " . $configuration->getAttribute('schemeIdUri'),
            );


            $value = $configuration->getAttribute('value');
            $correctValue = strlen($value) == 4 && ctype_xdigit($value);

            $this->dolbyCase->pathAdd(
                path: $adaptationSet->path() . "@$configurationIndex",
                result: $correctValue,
                severity: "FAIL",
                pass_message: "Value correct",
                fail_message: "Value incorrect - $value",
            );
        }
    }

    private function validateRoles(Period $period): void
    {
        $audioAdaptationCount = 0;
        foreach ($period->allAdaptationSets() as $adaptationSet) {
            if ($adaptationSet->getAttribute('contentType') != 'audio') {
                continue;
            }
            $audioAdaptationCount++;
            $roles = $adaptationSet->getDOMElements('Role');

            $atLeastOneDashRole = false;

            foreach ($roles as $role) {
                if ($role->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011') {
                    $atLeastOneDashRole = true;
                    break;
                }
            }

            $this->roleCase->pathAdd(
                path: $adaptationSet->path(),
                result: $atLeastOneDashRole,
                severity: "FAIL",
                pass_message: "At least one Role element found",
                fail_message: "No Role elements found"
            );
        }
    }

    private function validateAttributes(AdaptationSet $adaptationSet): void
    {
        //NOTE: This only applies to non-NGA streams.
        foreach ($adaptationSet->allRepresentations() as $representation) {
            foreach (['mimeType', 'codecs','audioSamplingRate'] as $attribute) {
                $this->attributeCase->pathAdd(
                    path: $representation->path(),
                    result: $representation->getTransientAttribute($attribute) != '',
                    severity: "FAIL",
                    pass_message: "'@$attribute' found",
                    fail_message: "'@$attribute' missing",
                );
            }
                $this->attributeCase->pathAdd(
                    path: $representation->path(),
                    result: count($adaptationSet->getDOMElements('Role')) > 0 ||
                          count($representation->getDOMElements('Role')) > 0,
                    severity: "FAIL",
                    pass_message: "Role element(s) found",
                    fail_message: "No role element found"
                );
        }
    }

    /**
     * @param array<string, AdaptationSet> $audioAdaptationSetById;
     **/
    private function validateFallback(array $audioAdaptationSetById): void
    {
        // TODO: Re-add check for same role in fallback as in base
        foreach ($audioAdaptationSetById as $adaptationSet) {
            $supplementalProperties = $adaptationSet->getDOMElements('SupplementalProperty');
            foreach ($supplementalProperties as $supplementalProperty) {
                if ($supplementalProperty->getAttribute('schemeIdUri') != 'urn:dvb:dash:fallback_adaptation_set:2014') {
                    continue;
                }

                $this->fallbackCase->pathAdd(
                    path: $adaptationSet->path(),
                    result: array_key_exists($supplementalProperty->getAttribute('value'), $audioAdaptationSetById),
                    severity: "FAIL",
                    pass_message: "Corresponding AdaptationSet found",
                    fail_message: "Corresponding AdaptationSet not found",
                );
            }
        }
    }
}
