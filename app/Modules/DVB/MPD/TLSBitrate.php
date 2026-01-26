<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TLSBitrate
{
    private SubReporter $legacyreporter;

    private TestCase $sdCase;
    private TestCase $uhdCase;
    private TestCase $hfrCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyreporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "DVB",
            []
        ));

        $this->sdCase = $this->legacyreporter->add(
            section: "Bitrate information",
            test: "Terminal which does not support UHD video (max 12 Mbit/s)",
            skipReason: "No TLS stream"
        );

        $this->uhdCase = $this->legacyreporter->add(
            section: "Bitrate information",
            test: "Terminal which does not support UHD, but not UHD HFR video (max 39 Mbit/s)",
            skipReason: "No TLS stream"
        );

        $this->hfrCase = $this->legacyreporter->add(
            section: "Bitrate information",
            test: "Terminal which does supports UHD HFR video (max 51 Mbit/s)",
            skipReason: "No TLS stream"
        );
    }

    public function validateTLSBitrate(): void
    {
        $mpdCache = app(MPDCache::class);

        //Only applicable for TLS streams
        if (!str_starts_with($mpdCache->getBaseUrl(), "https://")) {
            return;
        }

        foreach ($mpdCache->allPeriods() as $period) {
            $this->constraintsByPeriod($period);
        }
    }

    /**
     * @return array<string, array<string, int>>
     **/
    private function extractBandwidths(Period $period): array
    {
        $res = [
            'video' => [],
            'audio' => [],
            'subtitle' => []
        ];

        foreach ($period->allAdaptationSets() as $adaptationSet) {
            foreach ($adaptationSet->allRepresentations() as $representation) {
                $representationId = $representation->path();
                $representationBandwith = $representation->getAttribute('bandwidth');
                $context = '';
                switch ($representation->getTransientAttribute('mimeType')) {
                    case 'video/mp4':
                        $context = 'video';
                        break;
                    case 'audio/mp4':
                        $context = 'audio';
                        break;
                    case 'application/mp4':
                        $context = 'subtitle';
                        break;
                }
                if ($context != '') {
                    $res[$context][$representationId] = $representationBandwith;
                }
            }
        }

        foreach (['video', 'audio','subtitle'] as $context) {
            if (count($res[$context]) == 0) {
                $res[$context]["None"] = 0;
            }
        }

        return $res;
    }

    private function constraintsByPeriod(Period $period): void
    {
        //Check if any combination excedes the constraint

        $bandwidth = $this->extractBandwidths($period);

        foreach ($bandwidth['video'] as $vRepId => $vRepBandwidth) {
            foreach ($bandwidth['audio'] as $aRepId => $aRepBandwidth) {
                foreach ($bandwidth['subtitle'] as $sRepId => $sRepBandwidth) {
                    $totalBandwidth = $vRepBandwidth + $aRepBandwidth + $sRepBandwidth;

                    $combinationMessage = implode(" + ", [
                        $vRepId,
                        $aRepId,
                        $sRepId
                    ]);

                    $msgPrefix = "[$combinationMessage]";
                    $inBoundsMessage = "within bounds";
                    $exceedsMessage = "exceeds bounds";


                    $this->hfrCase->pathAdd(
                        path: $msgPrefix,
                        result: $totalBandwidth <= 51000000,
                        severity: "WARN",
                        pass_message: $inBoundsMessage,
                        fail_message: $exceedsMessage
                    );
                    $this->uhdCase->pathAdd(
                        path: $msgPrefix,
                        result: $totalBandwidth <= 39000000,
                        severity: "WARN",
                        pass_message: $inBoundsMessage,
                        fail_message: $exceedsMessage
                    );

                    $this->sdCase->pathAdd(
                        path: $msgPrefix,
                        result: $totalBandwidth <= 12000000,
                        severity: "WARN",
                        pass_message: $inBoundsMessage,
                        fail_message: $exceedsMessage
                    );
                }
            }
        }
    }
}
