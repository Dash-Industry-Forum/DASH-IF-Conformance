<?php

namespace DASHIF;

class MPDHandler
{
    private $url;
    private $dom;
    private $features;
    private $profiles;
    private $resolved;

    public function __construct($url)
    {
        $this->url = $url;
        $this->dom = null;
        $this->features = null;
        $this->profiles = null;
        $this->resolved = null;

        $this->load();
        $this->features = $this->recursiveExtractFeatures($this->dom);
        $this->extractProfiles();
        $this->xlinkResolve();
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
}
