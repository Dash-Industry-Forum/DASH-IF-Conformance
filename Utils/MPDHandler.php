<?php

namespace DASHIF;

class MPDHandler
{
    private $url;
    private $mpd;
    private $dom;
    private $features;
    private $profiles;
    private $resolved;
    private $periodTimingInformation;
    private $schemaPath;
    private $mpdValidatorOutput;
    private $schematronOutput;
    private $schematronIssuesReport;

    private $downloadTime; //Datetimeimmutable

    private $selectedPeriod;
    private $selectedAdapationSet;
    private $selectedRepresentation;

    private $hls;
    private $hlsPlaylistArray;
    private $hlsManifestType;

    private $segmentUrls;

    public function __construct($url)
    {
        $this->url = $url;
        $this->mpd = null;
        $this->dom = null;
        $this->downloadTime = null;
        $this->features = null;
        $this->profiles = null;
        $this->resolved = null;
        $this->selectedPeriod = 0;
        $this->selectedAdaptationSet = 0;
        $this->selectedRepresentation = 0;
        $this->periodTimingInformation = array();
        $this->schemaPath = null;
        $this->mpdValidatorOutput = null;
        $this->schematronOutput = null;
        $this->schematronIssuesReport = null;
        $this->segmentUrls = array();

        $this->load();
        $this->parseXML();
        if ($this->mpd) {
            $this->features = $this->recursiveExtractFeatures($this->dom);
            $this->extractProfiles();
            $this->runSchematron();
            $this->validateSchematron();
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
            $this->runSchematron();
            $this->validateSchematron();
            $this->loadSegmentUrls();
        }
        return true;
    }

    public function getEarliestUpdate(): \DateTimeImmutable | null
    {
        return include 'impl/MPDHandler/getEarliestUpdate.php';
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
        include 'impl/MPDHandler/downloadAll.php';
    }

    private function assembleSingle($source, $assembly, $sizeFile, $index)
    {
        include 'impl/MPDHandler/assembleSingle.php';
    }

    public function downloadSegment($target, $url)
    {
        include 'impl/MPDHandler/downloadSegment.php';
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
        return include 'impl/MPDHandler/getRoles.php';
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

    private function runSchematron()
    {
        include 'impl/MPDHandler/runSchematron.php';
    }

    private function validateSchematron()
    {
        include 'impl/MPDHandler/validateSchematron.php';
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
        return include 'impl/MPDHandler/recursiveExtractFeatures.php';
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
        return include 'impl/MPDHandler/computeDynamicIntervals.php';
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
        include 'impl/MPDHandler/parseXML.php';
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

    public function getResolved()
    {
        return $this->resolved;
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

    protected function setFeatures($features)
    {
        $this->features = $features;
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
