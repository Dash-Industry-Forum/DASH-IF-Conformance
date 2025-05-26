<?php

namespace App\Services;

use App\Services\ModuleLogger;
use App\Services\Schematron;

class MPDHandler
{
    private $url;
    private $mpd;
    private $dom;
    private $features;
    private $profiles;
    private string $resolved;
    private $periodTimingInformation;


    private Schematron $schematron;

    private $downloadTime; //Datetimeimmutable

    private $selectedPeriod;
    private $selectedAdaptationSet;
    private $selectedRepresentation;

    private $hls;
    private $hlsPlaylistArray;
    private $hlsManifestType;

    private $segmentUrls;


    public function __construct()
    {
        $this->url = session()->get('mpd');
        $this->mpd = null;
        $this->dom = null;
        $this->downloadTime = null;
        $this->features = null;
        $this->profiles = null;
        $this->selectedPeriod = 0;
        $this->selectedAdaptationSet = 0;
        $this->selectedRepresentation = 0;
        $this->periodTimingInformation = array();
        $this->schematron = new Schematron();
        $this->segmentUrls = array();

        $this->load();
        $this->parseXML();
        if ($this->mpd) {
            $this->schematron = new Schematron($this->mpd);
            $this->features = $this->recursiveExtractFeatures($this->dom);
            $this->extractProfiles();
            $this->loadSegmentUrls();
        }
    }


    public function refresh($content = null)
    {
        $tmpMpd = $this->mpd;
        if (!$content) {
            $this->load();
        } else {
            $this->mpd = $content;
        }
        $this->parseXML();
        if ($this->mpd == $tmpMpd) {
            return false;
        }
        $this->features = $this->recursiveExtractFeatures($this->dom);
        $this->extractProfiles();
        if (!$content) {
            $this->schematron->validateSchematron();
            $this->loadSegmentUrls();
        }
        return true;
    }

    public function getEarliestUpdate(): \DateTimeImmutable | null
    {
        if (!$this->downloadTime) {
            return null;
        }
        if (!$this->dom) {
            return null;
        }

//TODO Explicit check for type is dynamic

        $mpdElement = $this->dom;
        if ($mpdElement->tagName != "MPD") {
            $mpdElements = $this->dom->getElementsByTagName("MPD");
            if (!count($mpdElements)) {
                Log::error("No MPD element in dom");
                return null;
            }
            $mpdElement = $mpdElements->item(0);
        }


        $minimumUpdatePeriod = $mpdElement->getAttribute('minimumUpdatePeriod');

        if (!$minimumUpdatePeriod) {
            return null;
        }

        $interval = DASHIF\Utility\timeParsing($minimumUpdatePeriod);

        if (!$interval) {
            return null;
        }

        $originalTime = $this->downloadTime->getTimestamp();
        $nextTime = $originalTime + $interval;
        return new DateTimeImmutable("@$nextTime");
    }

    public function getPeriodAttribute($idx, $attr): string | null
    {
        if (!array_key_exists($attr, $this->features["Period"][$idx])) {
            return null;
        }
        return $this->features["Period"][$idx][$attr];
    }

    public function getAdaptationSetAttribute($idx, $aIdx, $attr): string | null
    {
        $adaptationSetFeatures = $this->features["Period"][$idx]["AdaptationSet"][$aIdx];
        if (!array_key_exists($attr, $adaptationSetFeatures)) {
            return null;
        }
        return $adaptationSetFeatures[$attr];
    }
    public function getAdaptationSetChild($idx, $aIdx, $childName)
    {
        $adaptationSetFeatures = $this->features["Period"][$idx]["AdaptationSet"][$aIdx];
        if (!array_key_exists($childName, $adaptationSetFeatures)) {
            return null;
        }
        return $adaptationSetFeatures[$childName];
    }
    public function getRepresentationAttribute($idx, $aIdx, $rIdx, $attr): string | null
    {
        $representationFeatures = $this->features["Period"][$idx]["AdaptationSet"][$aIdx]['Representation'][$rIdx];
        if (!array_key_exists($attr, $representationFeatures)) {
            return null;
        }
        return $representationFeatures[$attr];
    }


    public function downloadAll($assemble = true)
    {
        global $session, $limit;

        $periodIdx = 0;

        foreach ($this->segmentUrls as $periodIdx => $periodUrls) {
            if ($limit != 0 && $periodIdx >= $limit) {
                break;
            }
            $adaptationIdx = 0;
            foreach ($periodUrls as $adaptationIdx => $adaptationUrls) {
                if ($limit != 0 && $adaptationIdx >= $limit) {
                    break;
                }
                foreach ($adaptationUrls as $representationIdx => $representationUrls) {
                    $dir = $session->getRepresentationDir($periodIdx, $adaptationIdx, $representationIdx);
                    $assembly = ($assemble ? fopen("$dir/assembled.mp4", 'a+') : null);
                    $sizeFile = ($assemble ? fopen("$dir/assemblerInfo.txt", 'a+') : null);
                    $index = 0;

                    if (array_key_exists('init', $representationUrls)) {
                        $this->downloadSegment("$dir/init.mp4", $representationUrls['init']);

                        $this->assembleSingle("$dir/init.mp4", $assembly, $sizeFile, $index);
                        $index++;
                    }

                    foreach ($representationUrls['segments'] as $i => $url) {
                        $segmentPadded = sprintf('%02d', $i);
                        $this->downloadSegment("$dir/seg${segmentPadded}.mp4", $url);
                        $this->assembleSingle("$dir/seg${segmentPadded}.mp4", $assembly, $sizeFile, $index);
                        $index++;

                        if ($limit != 0 && $index >= $limit) {
                            break;
                        }
                    }


                    if ($assembly) {
                        fclose($assembly);
                    }

                    if ($sizeFile) {
                        fclose($sizeFile);
                    }
                }
                $adaptationIdx++;
            }
            $periodIdx++;
        }
    }

    private function assembleSingle($source, $assembly, $sizeFile, $index)
    {
        if (!$assembly) {
            return;
        }

        $toAppend = file_get_contents($source);
        fwrite($assembly, $toAppend);
        fwrite($sizeFile, "$index " . strlen($toAppend) . "\n");
    }

    public function downloadSegment($target, $url)
    {
        $fp = fopen($target, "w+");
        if (!$fp) {
            return;
        }

        $ch = curl_init();
        $curlOpts = DASHIF\Utility\curlOptions();
        $curlOpts[CURLOPT_URL] = $url;
        $curlOpts[CURLOPT_FILE] = $fp;
        curl_setopt_array($ch, $curlOpts);

        curl_exec($ch);
        fclose($fp);
    }

    public function internalSegmentUrls()
    {
        return $this->segmentUrls;
    }


    public function loadSegmentUrls()
    {
        return include 'impl/MPDHandler/loadSegmentUrls.php';
    }

    public function getRoles($period, $adaptation)
    {
        $res = array();

        $allPeriods = $this->dom->getElementsByTagName('Period');

        if (count($allPeriods) >= $period) {
            return $res;
        }

        $periodAdaptations = $allPeriods->item($period)->getElementsByTagName('AdaptationSet');

        if (count($periodAdaptations) >= $adaptation) {
            return $res;
        }

        $adaptationRoles = $periodAdaptations->item($adaptation)->getElementsByTagName('Role');

        foreach ($adaptationRoles as $role) {
            $res[] = array(
              'schemeIdUri' => $role->getAttribute('schemeIdUri'),
              'value' => $role->getAttribute('value'),
            );
        }

        return $res;
    }

    public function getPeriodIds()
    {
        return include 'impl/MPDHandler/getPeriodIds.php';
    }

    public function getAdaptationSetIds($periodId)
    {
        return include 'impl/MPDHandler/getAdaptationSetIds.php';
    }
    public function getRepresentationIds($periodId, $adaptationSetId)
    {
        return include 'impl/MPDHandler/getRepresentationIds.php';
    }


    public function selectPeriod($period)
    {
        $this->selectedPeriod = $period;
    }
    public function selectNextPeriod()
    {
        $this->selectedPeriod++;
    }
    public function getSelectedPeriod()
    {
        return $this->selectedPeriod;
    }

    public function selectAdaptationSet($adaptationSet)
    {
        $this->selectedAdaptationSet = $adaptationSet;
    }
    public function selectNextAdaptationSet()
    {
        $this->selectedAdaptationSet++;
    }
    public function getSelectedAdaptationSet()
    {
        return $this->selectedAdaptationSet;
    }

    public function selectRepresentation($representation)
    {
        $this->selectedRepresentation = $representation;
    }
    public function selectNextRepresentation()
    {
        $this->selectedRepresentation++;
    }
    public function getSelectedRepresentation()
    {
        return $this->selectedRepresentation;
    }

    public function getSchematronOutput()
    {
        return $this->schematronOutput;
    }


    private function findOrDownloadSchema()
    {
        include 'impl/MPDHandler/findOrDownloadSchema.php';
    }

    private function extractProfiles()
    {
        include 'impl/MPDHandler/extractProfiles.php';
    }

    private function recursiveExtractFeatures($node)
    {
        if (!$node) {
            return null;
        }

        $array = array();
        $attributes = $node->attributes;
        $children = $node->childNodes;

        foreach ($attributes as $attribute) {
            $array[$attribute->nodeName] = $attribute->nodeValue;
        }

        foreach ($children as $child) {
            if (!empty($child->nodeName) && $child->nodeType == XML_ELEMENT_NODE) {
                $array[$child->nodeName][] = $this->recursiveExtractFeatures($child);
            }
            if ($child->nodeName == 'BaseURL') {
                $array['BaseURL'][sizeof($array['BaseURL']) - 1]['anyURI'] = $child->firstChild->nodeValue;
            }
        }

        return $array;
    }

    public function getPeriodTimingInfo($periodIndex = null)
    {
        return $this->getPeriodDurationInfo($periodIndex ? $periodIndex : $this->selectedPeriod);
    }

    private function getPeriodDurationInfo($period)
    {
        return include 'impl/MPDHandler/getPeriodDurationInfo.php';
    }

    private function getDurationForAllPeriods()
    {
        include 'impl/MPDHandler/getDurationsForAllPeriods.php';
    }

    public function getPeriodBaseUrl($periodIndex = null)
    {

        return include 'impl/MPDHandler/getPeriodBaseUrl.php';
    }

    public function getSegmentUrls($periodIndex = null)
    {
        return include 'impl/MPDHandler/getSegmentUrls.php';
    }

    public function getFrameRate(
        $periodIndex = null,
        $adaptationIndex = null,
        $representationIndex = null
    ) {
        return include 'impl/MPDHandler/getFrameRate.php';
    }

    public function getContentType(
        $periodIndex = null,
        $adaptationIndex = null,
        $representationIndex = null
    ) {
        return include 'impl/MPDHandler/getContentType.php';
    }



    private function computeTiming(
        $presentationDuration,
        $segmentAccess,
        $segmentAccessType
    ) {
        return include 'impl/MPDHandler/computeTiming.php';
    }

    private function computeDynamicIntervals(
        $adaptationSetId,
        $representationId,
        $segmentAccess,
        $segmentTimings,
        $segmentCount
    ) {
        ///\Todo Bring this file up to naming specs
        global $period_timing_info, $modules, $availability_times;

        $bufferduration = ($this->features['timeShiftBufferDepth'] != null) ?
        DASHIF\Utility\timeParsing($this->features['timeShiftBufferDepth']) : INF;

        $AST = $this->features['availabilityStartTime'];
        $segmentduration = 0;
        if ($segmentAccess['SegmentTimeline'] != null) {
            if (count($segmentTimings) > 1) {
                $segmentduration = (
                    $segmentTimings[$segmentCount - 1] - $segmentTimings[0]) /
                    ((float)($segmentCount - 1));
            }
        } else {
            $segmentduration = ($segmentAccess['duration'] != null) ? $segmentAccess['duration'] : 0;
        }
        $timescale = ($segmentAccess['timescale'] != null) ? $segmentAccess['timescale'] : 1;
        $availabilityTimeOffset = (array_key_exists("availabilityTimeOffset", $segmentAccess) &&
        $segmentAccess['availabilityTimeOffset'] != 'INF') ? $segmentAccess['availabilityTimeOffset'] : 0;

        $pto = ($segmentAccess['presentationTimeOffset'] != '') ?
        (int)($segmentAccess['presentationTimeOffset']) / $timescale : 0;

        if ($segmentduration != 0) {
            $segmentduration /= $timescale;
        }

        $avgsum = array();
        $sumbandwidth = array();
        $adaptation_sets = $this->features['Period'][$this->selectedPeriod]['AdaptationSet'];
        for ($k = 0; $k < sizeof($adaptation_sets); $k++) {
            $representations = $adaptation_sets[$k]['Representation'];
            $sum = 0;
            for ($l = 0; $l < sizeof($representations); $l++) {
                $sum += $representations[$l]['bandwidth'];
            }

            $sumbandwidth[] = $sum;
            $avgsum[] = $sum / sizeof($representations);
        }
        $sumbandwidth = array_sum($sumbandwidth);
        $avgsum = array_sum($avgsum) / sizeof($avgsum);
        $percent = $avgsum / $sumbandwidth;

        if ($segmentduration == 0) {
            $segmentduration = 1;
        }

        $buffercapacity = $bufferduration / $segmentduration; //actual buffer capacity

        date_default_timezone_set("UTC"); //Set default timezone to UTC
        $now = time(); // Get actual time
        $AST = strtotime($AST);
        $LST = $now - ($AST + $period_timing_info["start"] - $pto - $availabilityTimeOffset - $segmentduration);
        $LSN = intval($LST / $segmentduration);
        $earliestsegment = $LSN - $buffercapacity * $percent;

        $new_array = $segmentTimings;
        $new_array[] = $LST * $timescale;
        sort($new_array);
        $ind = array_search($LST * $timescale, $new_array);

        $SST = ($ind - 1 - $buffercapacity * $percent < 0) ? 0 : $ind - 1 - $buffercapacity * $percent;

        foreach ($modules as $module) {
            if ($module->name == "DASH-IF Low Latency") {
                if ($module->isEnabled()) {
                    $ASAST = array();
                    $NSAST = array();
                    $count = $LSN - intval($earliestsegment);
                    for ($i = $count; $i > 0; $i--) {
                          $ASAST[] = $now - $LST - $bufferduration * $i;
                          $NSAST[] = $now - ($LST - $bufferduration * $i + $availabilityTimeOffset);
                    }
                    $availability_times[$adaptationSetId][$representationId]['ASAST'] = $ASAST;
                    $availability_times[$adaptationSetId][$representationId]['NSAST'] = $NSAST;
                }
                break;
            }
        }

        return [intval($earliestsegment), $LSN, $SST];
    }


    private function computeUrls(
        $representation,
        $adaptationSetId,
        $representationId,
        $segmentAccess,
        $segmentInfo,
        $baseUrl
    ) {
        return include 'impl/MPDHandler/computeUrls.php';
    }

    private function load()
    {
        include 'impl/MPDHandler/load.php';
    }

    private function parseXML()
    {
        if (!$this->mpd) {
            return;
        }
        $simpleXML = simplexml_load_string($this->mpd);
        if (!$simpleXML) {
            Log::error("Invalid xml string as mpd", ['mpd' => $this->mpd]);
            return;
        }

        $domSxe = dom_import_simplexml($simpleXML);
        if (!$domSxe) {
            Log::error("Unable to import xml");
            return;
        }

        $dom = new \DOMDocument('1.0');
        $domSxe = $dom->importNode($domSxe, true);
        if (!$domSxe) {
            return;
        }

        $dom->appendChild($domSxe);
        $main_element_nodes = $dom->getElementsByTagName('MPD');
        if ($main_element_nodes->length == 0) {
            Log::error("No MPD in xml");
            $this->dom = null;
            return;
        }


        $this->dom = $main_element_nodes->item(0);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getMPD()
    {
        return $this->mpd;
    }

    public function getDom()
    {
        return $this->dom;
    }

    public function getResolved(): string
    {
        return $this->schematron->resolved;
    }

    public function getFeatures()
    {
        return $this->features;
    }
    public function getFeature($featureName)
    {
        if (!array_key_exists($featureName, $this->features)) {
            return null;
        }
        return $this->features[$featureName];
    }

    public function getProfiles()
    {
        return $this->profiles;
    }

    public function getAllPeriodFeatures()
    {
        return $this->features['Period'];
    }

    public function getCurrentPeriodFeatures()
    {
        return $this->features['Period'][$this->selectedPeriod];
    }
}
