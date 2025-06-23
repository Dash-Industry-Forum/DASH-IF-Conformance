<?php

namespace App\Services;

use App\Services\ModuleLogger;
use App\Services\Schematron;
use App\Services\MPDSelection;
use App\Services\Manifest\Period;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;

class MPDHandler
{
    private string $url = '';
    private string $mpd = '';
    private \DOMElement|null $dom;
    private mixed $features;
    private mixed $oldProfiles;
    private mixed $periodTimingInformation;


    private \DateTimeImmutable|null $downloadTime = null;

    private mixed $segmentUrls;

    /**
     * @var array<Period> $periods;
     **/
    private array $periods = [];

    private Schematron $schematron;

    public MPDSelection $selected;



    public function __construct()
    {
        $this->schematron = new Schematron();
        $this->selected = new MPDSelection();

        $this->url = session()->get('mpd') ?? Cache::get('CLI.URL', '');
        $this->dom = null;
        $this->downloadTime = null;
        $this->features = null;
        $this->oldProfiles = null;
        $this->periodTimingInformation = array();
        $this->segmentUrls = array();

        $this->load();
        $this->parseXML();
        $this->setPeriods();

        if ($this->mpd) {
//            $this->schematron = new Schematron($this->mpd);
//            $this->features = $this->recursiveExtractFeatures($this->dom);
//            $this->extractProfiles();
//            $this->loadSegmentUrls();
        }
    }

    private function setPeriods(): void
    {
        $this->periods = array();
        foreach ($this->dom->getElementsByTagName('Period') as $periodIndex => $period) {
            $this->periods[] = new Period($period, $periodIndex);
        }
    }



    public function refresh(mixed $content = null): bool
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
            $this->schematron->validate();
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

        $interval = timeParsing($minimumUpdatePeriod);

        if (!$interval) {
            return null;
        }

        $originalTime = $this->downloadTime->getTimestamp();
        $nextTime = $originalTime + $interval;
        return new \DateTimeImmutable("@$nextTime");
    }


    /**
     * @return array<Period>
     **/
    public function getPeriods(): array
    {
        return $this->periods;
    }

    public function getPeriod(int $idx = -1): Period | null
    {
        $index = $this->selected->getSelectedPeriod($idx);
        if ($index >= count($this->periods)) {
            return null;
        }
        return $this->periods[$index];
    }

    public function getAdaptationSetChild(int $idx, int $aIdx, string $childName): mixed
    {
        $adaptationSetFeatures = $this->features["Period"][$idx]["AdaptationSet"][$aIdx];
        if (!array_key_exists($childName, $adaptationSetFeatures)) {
            return null;
        }
        return $adaptationSetFeatures[$childName];
    }


    public function downloadAll(bool $assemble = true): void
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

    private function assembleSingle(string $source, mixed $assembly, mixed $sizeFile, int $index): void
    {
        if (!$assembly) {
            return;
        }

        $toAppend = file_get_contents($source);
        fwrite($assembly, $toAppend);
        fwrite($sizeFile, "$index " . strlen($toAppend) . "\n");
    }

    public function downloadSegment(string $target, string $url): void
    {
        $fp = fopen($target, "w+");
        if (!$fp) {
            return;
        }

        $ch = curl_init();
        $curlOpts = curlOptions();
        $curlOpts[CURLOPT_URL] = $url;
        $curlOpts[CURLOPT_FILE] = $fp;
        curl_setopt_array($ch, $curlOpts);

        curl_exec($ch);
        fclose($fp);
    }

    public function internalSegmentUrls(): mixed
    {
        return $this->segmentUrls;
    }


    public function loadSegmentUrls(): void
    {
        if (!$this->mpd || !$this->dom) {
            return;
        }

        $this->segmentUrls = array();

        $xmlPeriods = $this->dom->getElementsByTagName('Period');


        $mpdAsArray = $this->features;

        if (!array_key_exists('Period', $mpdAsArray)) {
            return;
        }

        foreach ($mpdAsArray['Period'] as $periodIdx => $period) {
            $baseUrls = $this->getPeriodBaseUrl($periodIdx);
            $periodTimingInfo = $this->getPeriodTimingInfo($periodIdx);

            $currentTemplate = null;
            if (array_key_exists("SegmentTemplate", $period)) {
                $currentBase = $period['SegmentTemplate'];
            }
            $currentBase = '';
            if (array_key_exists("SegmentBase", $period)) {
                $currentBase = $period['SegmentBase'];
            }

            $periodUrls = array();

            $adaptations = $period['AdaptationSet'];
            foreach ($adaptations as $adaptationIdx => $adaptation) {
                if (array_key_exists("SegmentTemplate", $adaptation)) {
                    $currentTemplate = mergeSegmentAccess(
                        $currentTemplate,
                        $adaptation['SegmentTemplate']
                    );
                }
                if (array_key_exists("SegmentBase", $adaptation)) {
                    $currentBase = mergeSegmentAccess(
                        $currentBase,
                        $adaptation['SegmentBase']
                    );
                }

                $adaptationUrls = array();

                foreach ($adaptation['Representation'] as $representationIdx => $representation) {
                    if (array_key_exists("SegmentTemplate", $representation)) {
                        $currentTemplate = mergeSegmentAccess(
                            $currentTemplate,
                            $representation['SegmentTemplate']
                        );
                    }
                    if (array_key_exists("SegmentBase", $representation)) {
                        $currentBase = mergeSegmentAccess(
                            $currentBase,
                            $representation['SegmentBase']
                        );
                    }


                    //\TODO We should probably also check for empty array?
                    if (!$currentTemplate) {
                        $adaptationUrls[] = array($baseUrls[$adaptationIdx][$representationIdx]);
                        continue;
                    }

                    $segmentInfo = $this->computeTiming(
                        $periodTimingInfo['duration'],
                        $currentTemplate[0],
                        'SegmentTemplate'
                    );
                    $urlObj = array();
                    $urlObj['segments'] = $this->computeUrls(
                        $representation,
                        $adaptationIdx,
                        $representationIdx,
                        $currentTemplate[0],
                        $segmentInfo,
                        $baseUrls[$adaptationIdx][$representationIdx]
                    );
                    if (array_key_exists('initialization', $currentTemplate[0])) {
                        $urlObj['init'] = array_shift($urlObj['segments']);
                    }
                    $adaptationUrls[] = $urlObj;
                }

                $periodUrls[] = $adaptationUrls;
            }

            $this->segmentUrls[] = $periodUrls;
        }
    }

    public function getRoles(int $period, int $adaptation): mixed
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

    /**
     * @return array<string>
     **/
    public function getPeriodIds(): array
    {
        $result = array();
        foreach ($this->periods as $p) {
            $result[] = $p->getAttribute('id');
        }
        return $result;
    }

    private function extractProfiles(): void
    {
        if (!$this->features) {
            return;
        }
        if (!array_key_exists("Period", $this->features)) {
            return;
        }

        $this->oldProfiles = array();
        $periods = $this->features['Period'];

        foreach ($periods as $period) {
            $adapts = $period['AdaptationSet'];
            $adapt_profiles = array();
            foreach ($adapts as $adapt) {
                $reps = $adapt['Representation'];
                $rep_profiles = array();
                foreach ($reps as $rep) {
                    $profiles = $this->features['profiles'];

                    if (array_key_exists('profiles', $period) && $period['profiles']) {
                        $profiles = $period['profiles'];
                    }

                    if (array_key_exists('profile', $adapt) && $adapt['profile']) {
                        $profiles = $adapt['profiles'];
                    }

                    if (array_key_exists('profile', $rep) && $rep['profile']) {
                        $profiles = $rep['profiles'];
                    }

                    $rep_profiles[] = $profiles;
                }
                $adapt_profiles[] = $rep_profiles;
            }
            $this->oldProfiles[] = $adapt_profiles;
        }
    }

    private function recursiveExtractFeatures(mixed $node): mixed
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

    public function getPeriodTimingInfo(int $periodIndex = -1): mixed
    {
        return $this->getPeriodDurationInfo($this->selected->getSelectedPeriod($periodIndex));
    }

    private function getPeriodDurationInfo(int $period): mixed
    {
        global $period_timing_info;

        if (empty($this->periodTimingInformation)) {
            $this->getDurationForAllPeriods();
        }


        $period_timing_info = $this->periodTimingInformation[$period];

        return $period_timing_info;
    }

    private function getDurationForAllPeriods(): void
    {
        $periods = $this->features['Period'];

        $mediapresentationduration = 0;
/* TODO
if (array_key_exists("mediaPresentationDuration", $this->features)) {
    $mediapresentationduration = timeParsing(
        $this->features['mediaPresentationDuration']
    );
}*/

        $this->periodTimingInformation = array();

        for ($i = 0; $i < sizeof($periods); $i++) {
            $period = $periods[$i];

            $periodStart = '';
            if (array_key_exists('start', $period)) {
                $periodStart = $period['start'];
            }
            $periodDuration = '';
            if (array_key_exists('duration', $period)) {
                $periodDuration = $period['duration'];
            }

            $start = 0;
            if ($periodStart != '') {
                //TODO
                //$start = timeParsing($periodStart);
            } else {
                if ($i > 0) {
                    $previous =  $this->periodTimingInformation[$i - 1];
                    if ($previous['duration'] != '') {
                        $start = (float)($previous['start'] + $previous['duration']);
                    } else {
                        $start = '';
                        if ($this->features['type'] == 'dynamic') {
                          ///\todo handle early available period
                        }
                    }
                } else {
                    if ($this->features['type'] == 'static') {
                        $start = 0;
                    } else {
                        $start = '';
                        if ($this->features['type'] == 'dynamic') {
                          ///\todo handle early available period
                        }
                    }
                }
            }

            $duration = 0;
            if ($periodDuration != '' && $periodDuration != null) {
                //TODO $duration = timeParsing($periodDuration);
            } else {
                if ($i != sizeof($periods) - 1) {
                    //TODO $duration = timeParsing($periods[$i + 1]['start']) - $start;
                } else {
                    $duration = $mediapresentationduration - $start;
                }
            }

            $this->periodTimingInformation[] = array(
              'start' => $start,
              'duration' => min([$duration, 1800])
            );
        }
    }

    public function getPeriodBaseUrl(int $periodIndex = -1): mixed
    {
        $periodIdx = $this->selected->getSelectedPeriod($periodIndex);

        $mpdBaseUrl = null;
        if (array_key_exists("BaseURL", $this->features)) {
            $mpdBaseUrl =  $this->features['BaseURL'];
        }

        $period = $this->features['Period'][$periodIdx];
        $periodBaseUrl = null;
        if (array_key_exists("BaseURL", $period)) {
            $periodBaseUrl = $period['BaseURL'];
        }

        $adaptationUrls = array();

        $adaptations = $period['AdaptationSet'];
        foreach ($adaptations as $adaptation) {
            $representationUrls = array();
            $adaptationBaseUrl = null;
            if (array_key_exists("BaseURL", $adaptation)) {
                $adaptationBaseUrl = $adaptation['BaseURL'];
            }

            $representations = $adaptation['Representation'];
            foreach ($representations as $representation) {
                $representationUrl = '';
                $representationBaseUrl = null;
                if (array_key_exists("BaseURL", $representation)) {
                    $representationBaseUrl  = $representation['BaseURL'];
                }

                if ($mpdBaseUrl || $periodBaseUrl || $adaptationBaseUrl || $representationBaseUrl) {
                    $url = '';
                    $urlParts = array($mpdBaseUrl, $periodBaseUrl, $adaptationBaseUrl, $representationBaseUrl);
                    foreach ($urlParts as $urlPart) {
                        if ($urlPart) {
                            $base = $urlPart[0]['anyURI'];
                            //if (isAbsoluteURL($base)) {
                                $url = $base;
                            Log::warning("Re-implement non-base url!");
                            //} else {
                            //    $url .=  $base;
                            //}
                        }
                    }
                    $representationUrl = $url;
                }
                if ($representationUrl == '') {
                    $representationUrl = dirname($this->url) . '/';
                }
                    Log::warning("Re-implement non-base url!");
        //        if (!isAbsoluteURL($representationUrl)) {
        //            $representationUrl = dirname($this->url) . '/' . $representationUrl;
        //        }


                $representationUrls[] = $representationUrl;
            }
            $adaptationUrls[] = $representationUrls;
        }
        return $adaptationUrls;
    }

    public function getSegmentUrls(int $periodIndex = -1): mixed
    {
        global $segment_accesses;

        $periodIdx = $this->selected->getSelectedPeriod($periodIndex);

        $periodTimingInfo = $this->getPeriodTimingInfo($periodIdx);
        $baseUrls = $this->getPeriodBaseUrl($periodIdx);

        $period = $this->features['Period'][$periodIdx];
        $adaptationSets = $period['AdaptationSet'];
        $adaptationSegmentUrls = array();

        foreach ($adaptationSets as $adaptationIndex => $adaptationSet) {
            $segmentTemplateAdaptation = mergeSegmentAccess(
                $period['SegmentTemplate'],
                $adaptationSet['SegmentTemplate']
            );
            $segmentBaseAdaptation = mergeSegmentAccess(
                $period['SegmentBase'],
                $adaptationSet['SegmentBase']
            );



            $representations = $adaptationSet['Representation'];
            $segmentAccess = array();
            $segmentUrls = array();
            foreach ($representations as $representationIndex => $representation) {
                $segmentTemplate = mergeSegmentAccess(
                    $segmentTemplateAdaptation,
                    $representation['SegmentTemplate']
                );
                $segmentBase = mergeSegmentAccess(
                    $segmentBaseAdaptation,
                    $representation['SegmentBase']
                );

                if ($segmentTemplate) {
                            $segmentAccess[] = $segmentTemplate;
                            $segmentInfo = $this->computeTiming(
                                $periodTimingInfo['duration'],
                                $segmentTemplate[0],
                                'SegmentTemplate'
                            );
                            $segmentUrls[] = $this->computeUrls(
                                $representation,
                                $adaptationIndex,
                                $representationIndex,
                                $segmentTemplate[0],
                                $segmentInfo,
                                $baseUrls[$adaptationIndex][$representationIndex]
                            );
                            continue;
                }
                if ($segmentBase) {
                    $segmentAccess[] = $segmentBase;
                    $segmentUrls[] = array($baseUrls[$adaptationIndex][$representationIndex]);
                    continue;
                }
                $segmentAccess[] = '';
                $segmentUrls[] = array($baseUrls[$adaptationIndex][$representationIndex]);
            }
            $adaptationSegmentUrls[] = $segmentUrls;
            $segment_accesses[] = $segmentAccess;
        }

        return $adaptationSegmentUrls;
    }

    public function getFrameRate(
        int $periodIndex = -1,
        int $adaptationIndex = -1,
        int $representationIndex = -1
    ): mixed {
        $period = $this->selected->getSelectedPeriod($periodIndex);
        $adaptation = $this->selected->getSelectedAdaptationSet($adaptationIndex);
        $representation = $this->selected->getSelectedRepresentation($representationIndex);

        $framerate = 0;

        $periods = $this->dom->getElementsByTagName("Period");

        if ($period >= count($periods)) {
            return null;
        }

        $thisPeriod = $periods->item($period);

        if ($thisPeriod->hasAttribute('frameRate')) {
            $framerate = $thisPeriod->getAttribute('frameRate');
        }

        $adaptationSets = $thisPeriod->getElementsByTagName("AdaptationSet");
        if ($adaptation >= count($adaptationSets)) {
            return null;
        }

        $thisAdaptation = $adaptationSets->item($adaptation);

        if ($thisAdaptation->hasAttribute('frameRate')) {
            $framerate = $thisAdaptation->getAttribute('frameRate');
        }

        $representations = $thisAdaptation->getElementsByTagName("Representation");

        if ($representation >= count($representations)) {
            return null;
        }

        $thisRepresentation = $representations->item($representation);

        if ($thisRepresentation->hasAttribute('frameRate')) {
            $framerate = $thisRepresentation->getAttribute('frameRate');
        }

        return $framerate;
    }

    public function getContentType(
        int $periodIndex = -1,
        int $adaptationIndex = -1,
    ): string {
        $period = $this->selected->getSelectedPeriod($periodIndex);
        $adaptation = $this->selected->getSelectedAdaptationSet($adaptationIndex);

        $periods = $this->dom->getElementsByTagName("Period");

        if ($period >= count($periods)) {
            return '';
        }

        $thisPeriod = $periods->item($period);

        $adaptationSets = $thisPeriod->getElementsByTagName("AdaptationSet");
        if ($adaptation >= count($adaptationSets)) {
            return '';
        }


        return $adaptationSets->item($adaptation)->getAttribute("contentType");
    }



    private function computeTiming(
        float $presentationDuration,
        mixed $segmentAccess,
        string $segmentAccessType
    ): mixed {
        if ($segmentAccessType == 'SegmentBase') {
            return array(0);
        }

        if ($segmentAccessType != 'SegmentTemplate') {
            return  array();
        }

        $segmentCount = 0;

///\Note start is always 0... leads to negative segmentStartTimes?

        $start = 0;

        $duration = 0;
        if (array_key_exists("duration", $segmentAccess)) {
            $duration = $segmentAccess['duration'];
        }

        $timescale = 1;
        if (array_key_exists("timescale", $segmentAccess)) {
            $timescale = $segmentAccess['timescale'];
        }

        $availabilityTimeOffset = 0;
        if (
            array_key_exists(
                "availabilityTimeOffset",
                $segmentAccess
            ) && $segmentAccess['availabilityTimeOffset'] != 'INF'
        ) {
            $availabilityTimeOffset =  $segmentAccess['availabilityTimeOffset'];
        }

        $presentationTimeOffset = 0;
        if (
            array_key_exists(
                "presentationTimeOffset",
                $segmentAccess
            ) && $segmentAccess['presentationTimeOffset'] != ''
        ) {
            $presentationTimeOffset = (int)($segmentAccess['presentationTimeOffset']) / $timescale;
        }

        if ($duration != 0) {
            $duration /= $timescale;
            $segmentCount = ceil(($presentationDuration - $start) / $duration);
        }

        $timeOffset = $presentationTimeOffset + $availabilityTimeOffset;
        $segmentTimings = array();

        if (!array_key_exists("Segment(Timeline", $segmentAccess)) {
            $segmentStartTime = $start - $timeOffset;

            for ($index = 0; $index < $segmentCount; $index++) {
                $segmentTimings[] = ($segmentStartTime + ($index * $duration));
            }
            return $segmentTimings;
        }

        $segmentTimeline = $segmentAccess['SegmentTimeline'];

        $segmentEntries = $segmentTimeline[0]['S'];

        if ($segmentEntries == null) {
            return array();
        }


        $segmentTime = 0;
        if ($segmentEntries[0]['t']) {
            $segmentTime =  $segmentEntries[0]['t'] ;
        }
        $segmentTime -= $timeOffset;

        foreach ($segmentEntries as $index => $segmentEntry) {
            $d = $segmentEntry['d'];
            $r = 0;
            if (array_key_exists("r", $segmentEntry)) {
                $r = $segmentEntry['r'];
            }
            $t = 0;
            if (array_key_exists("t", $segmentEntry)) {
                $t = $segmentEntry['t'];
            }
            $t -= $timeOffset;

            if ($r == 0) {
                $segmentTimings[] = (float) $segmentTime;
                $segmentTime += $d;
                continue;
            }
            if ($r < 0) {
                $endTime = $presentationDuration * $timescale;
                if (isset($segmentEntries[$index + 1])) {
                    $endTime = ($segmentEntries[$index + 1]['t']);
                }

                while ($segmentTime < $endTime) {
                    $segmentTimings[] = (float) $segmentTime;
                    $segmentTime += $d;
                }
                continue;
            }
            for ($repeat = 0; $repeat <= $r; $repeat++) {
                $segmentTimings[] = (float) $segmentTime;
                $segmentTime += $d;
            }
        }

        return $segmentTimings;
    }

    private function computeDynamicIntervals(
        int $adaptationSetId,
        int $representationId,
        mixed $segmentAccess,
        mixed $segmentTimings,
        int $segmentCount
    ): mixed {
        ///\Todo Bring this file up to naming specs
        global $period_timing_info, $modules, $availability_times;

        $bufferduration = ($this->features['timeShiftBufferDepth'] != null) ?
        timeParsing($this->features['timeShiftBufferDepth']) : INF;

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
        $adaptation_sets = $this->features['Period'][$this->selected->getSelectedPeriod()]['AdaptationSet'];
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
        mixed $representation,
        int $adaptationSetId,
        int $representationId,
        mixed $segmentAccess,
        mixed $segmentInfo,
        string $baseUrl
    ): mixed {
        $initialization = $segmentAccess['initialization'];
        $media = $segmentAccess['media'];
        $bandwidth = $representation['bandwidth'];
        $id = $representation['id'];

        $startNumber = 1;
        if (array_key_exists("startNumber", $segmentAccess)) {
            $startNumber =  $segmentAccess['startNumber'];
        }

        $segmentUrls = array();

        if ($initialization != null) {
            $initializationUrl = '';
            $init = str_replace(array('$Bandwidth$', '$RepresentationID$'), array($bandwidth, $id), $initialization);

            if (isAbsoluteURL($init)) {
                $segmentUrls[] = $init;
            } else {
                if (substr($baseUrl, -1) == '/') {
                    $url = $baseUrl . $init;
                } else {
                    $url = $baseUrl . "/" . $init;
                }
                $segmentUrls[] = $url;
            }
        }

        $currentTime = 0;

        $index = 0;
        $segmentCount = sizeof($segmentInfo);
        if ($this->features['type'] == 'dynamic') {
            list($index, $segmentCount, $currentTime) = $this->computeDynamicIntervals(
                $adaptationSetId,
                $representationId,
                $segmentAccess,
                $segmentInfo,
                $segmentCount
            );
        }

///\Todo translate checks below into actual "check"
        while ($index < $segmentCount) {
            $timeReplace = 0;
            if (array_key_exists($currentTime, $segmentInfo)) {
                $timeReplace = $segmentInfo[$currentTime];
            }
            $segmentUrl = str_replace(
                array('$Bandwidth$', '$Number$', '$RepresentationID$', '$Time$'),
                array(strval($bandwidth), strval($index + $startNumber), strval($id), strval($timeReplace)),
                $media
            );

            $pos = strpos($segmentUrl, '$Number');
            if ($pos !== false) {
                if (substr($segmentUrl, $pos + strlen('$Number'), 1) === '%') {
                    $segmentUrl = sprintf($segmentUrl, $startNumber + $index);
                    $segmentUrl = str_replace('$Number', '', $segmentUrl);
                    $segmentUrl = str_replace('$', '', $segmentUrl);
                } else {
                    Log::alert("It cannot happen! the format should be either \$Number$ or \$Number%xd$!");
                }
            }
            $pos = strpos($segmentUrl, '$Time');
            if ($pos !== false) {
                if (substr($segmentUrl, $pos + strlen('$Time'), 1) === '%') {
                    $segmentUrl = sprintf($segmentUrl, $segmentInfo[$index]);
                    $segmentUrl = str_replace('$Time', '', $segmentUrl);
                    $segmentUrl = str_replace('$', '', $segmentUrl);
                } else {
                    Log::alert("It cannot happen! the format should be either \$Time$ or \$Time%xd$!");
                }
            }

            if (isAbsoluteURL($segmentUrl)) {
                if (substr($baseUrl, -1) == '/') {
                    $segmentUrl = $baseUrl . $segmentUrl;
                } else {
                    $segmentUrl = $baseUrl . "/" . $segmentUrl;
                }
            }
            $segmentUrls[] = $segmentUrl;
            $index++;
            $currentTime++;
        }


        return $segmentUrls;
    }

    private function load(): void
    {
        global $session;

        $this->downloadTime = new \DateTimeImmutable();

        $isLocal = false;
        $localManifestLocation = '';

        if ($session) {
            $localManifestLocation = $session->getDir() . '/Manifest.mpd';
            if (isset($_FILES['mpd']) && move_uploaded_file($_FILES['mpd']['tmp_name'], $localManifestLocation)) {
                $this->url = $localManifestLocation;
                $isLocal = true;
            } elseif ($this->url) {
                if ($this->url[0] == '/') {
                    $isLocal = true;
                    copy($this->url, $localManifestLocation);
                } else {
                    //Download with CURL;
                    $this->downloadSegment($localManifestLocation, $this->url);
                    $isLocal = true;
                }
            }
        }

        if ($this->url) {
            if ($isLocal) {
                $this->mpd = file_get_contents($localManifestLocation);
            } else {
                $this->mpd = file_get_contents($this->url);
            }
        } elseif (isset($_REQUEST['mpd'])) {
            $this->mpd = $_REQUEST['mpd'];
        }


///\Todo: Check if this works with http basic auth
        if (!$this->mpd) {
            Log::critical("NO MPD");
            return;
        }
        Cache::add("CLI.MPD", $this->mpd, now()->addMinutes(1));
    }

    public function cacheXMLToDom(): void
    {
        Tracer::newSpan("Parse mpd")->measure(function () {
            $this->mpd = Cache::get(cache_path(['mpd']));
            $this->parseXML();
        });
    }

    private function parseXML(): void
    {
        if (!$this->mpd) {
            return;
        }
        $simpleXML = simplexml_load_string($this->mpd);
        if (!$simpleXML) {
            Log::error("Invalid xml string as mpd", ['mpd' => $this->mpd]);
            return;
        }

        //TODO: Add try/catch?
        $domSxe = dom_import_simplexml($simpleXML);

        $dom = new \DOMDocument('1.0');

        //TODO: Add try/catch?
        $domSxe = $dom->importNode($domSxe, true);

        $dom->appendChild($domSxe);
        $main_element_nodes = $dom->getElementsByTagName('MPD');
        if ($main_element_nodes->length == 0) {
            Log::error("No MPD in xml");
            $this->dom = null;
            return;
        }


        $this->dom = $main_element_nodes->item(0);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMPD(): string
    {
        return $this->mpd;
    }

    public function getDom(): \DOMElement|null
    {
        return $this->dom;
    }


    public function getFeatures(): mixed
    {
        return $this->features;
    }
    public function getFeature(string $featureName): mixed
    {
        if (!array_key_exists($featureName, $this->features)) {
            return null;
        }
        return $this->features[$featureName];
    }

    public function getProfiles(): mixed
    {
        return $this->oldProfiles;
    }

    /**
     * @return array<string>
     **/
    public function getMPDProfiles(): array
    {
        $res = array();
        if (!$this->dom) {
            return $res;
        }
        $profiles = $this->dom->getAttribute('profiles');
        return explode(',', $profiles);
    }

    public function getAllPeriodFeatures(): mixed
    {
        return $this->features['Period'];
    }

    public function getCurrentPeriodFeatures(): mixed
    {
        return $this->features['Period'][$this->selected->getSelectedPeriod()];
    }
}
