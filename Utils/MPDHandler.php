<?php

namespace DASHIF;

class MPDHandler
{

    private $url;
    private $dom;
    private $features;
    private $profiles;
    private $resolved;
    private $selectedPeriod;
    private $periodTimingInformation;

    public function __construct($url)
    {
        $this->url = $url;
        $this->dom = null;
        $this->features = null;
        $this->profiles = null;
        $this->resolved = null;
        $this->selectedPeriod = 0;
        $this->periodTimingInformation = array();

        $this->load();
        $this->features = $this->recursiveExtractFeatures($this->dom);
        $this->extractProfiles();
        $this->xlinkResolve();
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

    private function xlinkResolve()
    {
        include 'impl/MPDHandler/xlinkResolve.php';
    }

    private function xlinkResolveRecursive($node)
    {
        return include 'impl/MPDHandler/xlinkResoleRecursive.php';
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

    private function computeTiming($presentationDuration, $segmentAccess, $segmentAccessType)
    {
        return include 'impl/MPDHandler/computeTiming.php';
    }

    private function computeDynamicIntervals($adapatationSetId, $segmentAccess, $segmentTimings, $segmentCount)
    {
        return include 'impl/MPDHandler/computeDynamicIntervals.php';
    }


    private function computeUrls($representation, $adaptationSetId, $representationId, $segmentAccess, $segmentInfo, $baseUrl)
    {
        return include 'impl/MPDHandler/computeUrls.php';
    }

    private function load()
    {
        include 'impl/MPDHandler/load.php';
    }

    public function getDom()
    {
        return $this->dom;
    }

    public function getFeatures()
    {
        return $this->features;
    }

    public function getProfiles()
    {
        return $this->profiles;
    }

    public function getAllPeriodFeatures(){
      return $this->features['Period'];
    }

    public function getCurrentPeriodFeatures(){
      return $this->features['Period'][$this->selectedPeriod];
    }
}
