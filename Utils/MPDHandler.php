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

        $this->parseXML();
        if ($this->mpd) {
            $this->features = $this->recursiveExtractFeatures($this->dom);
            $this->extractProfiles();
            $this->runSchematron();
            $this->validateSchematron();
        }
    }


    public function refresh($content = null)
    {
        $tmpMpd = $this->mpd;
        if (!$content) {
          return;
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
        }
        return true;
    }

    public function getEarliestUpdate(): \DateTimeImmutable | null
    {
        return include 'impl/MPDHandler/getEarliestUpdate.php';
    }


    public function internalSegmentUrls()
    {
        return $this->segmentUrls;
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

}
