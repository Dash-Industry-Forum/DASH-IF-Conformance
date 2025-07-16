<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TLSBitrate
{
    private SubReporter $legacyreporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyreporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "LEGACY",
            []
        ));
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
                $representationId = $representation->getAttribute('id');
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
                $res[$context]["No $context"] = 0;
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

                    $bandWidthMessage = implode(", ", [
                        "V@" . number_format($vRepBandwidth / 1000000, 2) . "Mbit/s",
                        "A@" . number_format($aRepBandwidth / 1000000, 2) . "Mbit/s",
                        "S@" . number_format($sRepBandwidth / 1000000, 2) . "Mbit/s"
                    ]);
                    $totalBandWidthMessage = "Total: " . number_format($totalBandwidth / 1000000, 2) . "Mbit/s";

                    $combinationMessage = implode(", ", [
                        "V:" . $vRepId,
                        "A:" . $aRepId,
                        "S:" . $sRepId
                    ]);

                    $msgPrefix = "Period " . $period->path() . " ($combinationMessage)";
                    $inBoundsMessage = "$msgPrefix within bounds: $totalBandWidthMessage: $bandWidthMessage";
                    $exceedsMessage = "$msgPrefix exceeds bounds: $totalBandWidthMessage: $bandWidthMessage";


                    $this->legacyreporter->test(
                        "Unknown",
                        "Bitrate checks for terminal that does support UHD HFR video (max 51 Mbit/s)",
                        $totalBandwidth <= 51000000,
                        "WARN",
                        $inBoundsMessage,
                        $exceedsMessage
                    );
                    $this->legacyreporter->test(
                        "Unknown",
                        "Bitrate checks for terminal that does support UHD video, but not HFR video (max 39 Mbit/s)",
                        $totalBandwidth <= 39000000,
                        "WARN",
                        $inBoundsMessage,
                        $exceedsMessage
                    );
                    $this->legacyreporter->test(
                        "Unknown",
                        "Bitrate checks for terminal that does not support UHD video (max 12 Mbit/s)",
                        $totalBandwidth <= 12000000,
                        "WARN",
                        $inBoundsMessage,
                        $exceedsMessage
                    );
                }
            }
        }
    }
}
