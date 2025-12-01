<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CrossAudio
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $mimeTypeCase;
    private TestCase $sampleRateCase;
    private TestCase $audioChannelCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "DVB",
            "v1.4.1",
            []
        ));

        $this->mimeTypeCase = $this->v141Reporter->add(
            section: '6.1.1',
            test: "@mimeType SHALL match the mimetype derived from the segments",
            skipReason: "No audio stream found"
        );
        $this->sampleRateCase = $this->v141Reporter->add(
            section: '6.1.1',
            test: "@audioSamplingRate SHALL match the sample rate derived from the segments",
            skipReason: "No audio stream found"
        );
        $this->audioChannelCase = $this->v141Reporter->add(
            section: '6.1.1',
            test: "AudioChannelConfiguration SHALL match the configuration derived from the segments",
            skipReason: "No audio stream found"
        );
    }

    //Public validation functions
    public function validateAudioParameters(Representation $representation, Segment $segment): void
    {
        //We also want to check video tracks in MPD, as they may contain audio segments
        $this->validateMimeType($representation, $segment);

        $hdlr = $segment->getHandlerType();
        if ($hdlr != "soun") {
            return;
        }

        $sampleDescriptor = $segment->getAudioConfiguration();

        $segmentSampleRate = array_key_exists('SampleRate', $sampleDescriptor) ?  $sampleDescriptor['SampleRate'] : '';
        $representationSampleRate = $representation->getTransientAttribute('audioSamplingRate');
        $this->sampleRateCase->pathAdd(
            result: $segmentSampleRate == $representationSampleRate,
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Sampling rate matches",
            fail_message: "MPD ($representationSampleRate) does not match segment ($segmentSampleRate)",
        );

        $this->validateChannelCount($representation, $segment);
    }

    private function validateChannelCount(Representation $representation, Segment $segment): void
    {
        $sampleDescriptor = $segment->getAudioConfiguration();
        $segmentChannelCount = array_key_exists('Channels', $sampleDescriptor) ?  $sampleDescriptor['Channels'] : '';


        $mpdChannelCount = "";

        $representationChannelConfig = $representation->getDOMElements('AudioChannelConfiguration');
        if (count($representationChannelConfig) == 0) {
            $representationChannelConfig =
                $representation->getAdaptationSet()->getDOMElements('AudioChannelConfiguration');
        }
        Log::info(print_r($representationChannelConfig, true));
        foreach ($representationChannelConfig as $channelConfig) {
            if (
                $channelConfig->getAttribute('schemeIdUri') ==
                "urn:mpeg:dash:23003:3:audio_channel_configuration:2011"
            ) {
                $mpdChannelCount = $channelConfig->getAttribute('value');
            }
        }


        $this->audioChannelCase->pathAdd(
            result: $mpdChannelCount == $segmentChannelCount,
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Channel counts match",
            fail_message: "MPD ($mpdChannelCount) does not match segment ($segmentChannelCount)",
        );
    }


    private function validateMimeType(Representation $representation, Segment $segment): void
    {
        $hdlr = $segment->getHandlerType();
        $segmentMimeType = "";
        if ($hdlr == "vide") {
            $segmentMimeType = "video";
        }
        if ($hdlr == "soun") {
            $segmentMimeType = "audio";
        }

        $segmentMimeType .= "/mp4";
        $representationMimeType = $representation->getTransientAttribute('mimeType');

        if ($segmentMimeType == "audio/mp4" || $representationMimeType == "audio/mp4") {
            $this->mimeTypeCase->pathAdd(
                result: $segmentMimeType == $representationMimeType,
                severity: "FAIL",
                path: $representation->path() . "-init",
                pass_message: "Mimetype matches",
                fail_message: "MPD ($representationMimeType) does not match segment ($segmentMimeType)",
            );
        }
    }

    //Private helper functions
}
