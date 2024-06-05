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
        if ($this->mpd) {
            $this->features = $this->recursiveExtractFeatures($this->dom);
            $this->extractProfiles();
            $this->runSchematron();
            $this->validateSchematron();
            $this->loadSegmentUrls();
        }
    }

    public function refresh()
    {
        $tmpMpd = $this->mpd;
        $this->load();
        if ($this->mpd == $tmpMpd) {
            return false;
        }
        $this->features = $this->recursiveExtractFeatures($this->dom);
        $this->extractProfiles();
        $this->runSchematron();
        $this->validateSchematron();
        $this->loadSegmentUrls();
        return true;
    }

    public function getEarliestUpdate(): \DateTimeImmutable | null
    {
        return include 'impl/MPDHandler/getEarliestUpdate.php';
    }

    public function getPeriodAttribute($idx, $attr): string | null
    {
        return $this->features["Period"][$idx][$attr];
    }

    public function getAdaptationSetAttribute($idx, $aIdx, $attr): string | null
    {
        return $this->features["Period"][$idx]["AdaptationSet"][$aIdx][$attr];
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
        return include 'impl/MPDHandler/getPeriodTimingInfo.php';
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
