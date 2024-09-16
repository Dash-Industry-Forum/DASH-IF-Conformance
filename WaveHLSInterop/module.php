<?php

namespace DASHIF;

require_once __DIR__ . '/../Utils/moduleInterface.php';

class ModuleWaveHLSInterop extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CTA-WAVE - HLS Interop";
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        if ($argumentParser) {
            $argumentParser->addOption(
                "wavehlsinterop",
                "W",
                "wavehlsinterop",
                "Enable CTAWAVE - HLS Interop checking"
            );
        }
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("wavehlsinterop")) {
            $this->enabled = true;
        }
    }

    public function hookRepresentation()
    {
        parent::hookRepresentation();

        global $validatorWrapper, $mpdHandler;


        $thisRepresentation = array(
          $mpdHandler->getSelectedPeriod(),
          $mpdHandler->getSelectedAdaptationSet(),
          $mpdHandler->getSelectedRepresentation()
        );

        //Section 4.1  - Basic On-Demand and Live Streaming
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'textComponentConstraints'
        );
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'addressableMediaObject',
            ValidatorFlags::PreservesOrder
        );
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'bitrate'
        );

        //Section 4.3 - Encrypted Media Presentations
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'encryptionScheme',
            ValidatorFlags::CanExtractEncryption
        );

        //Section 4.4 - Presentation Splicing
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'splicingPoints',
            ValidatorFlags::PreservesOrder
        );
        
        //Section 4.5 - Carriage of Timed EventData
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'timedEventData',
            ValidatorFlags::PreservesOrder
        );

        //Section 4.6 - Rotation of Encryption Keys
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'keyRotation',
            ValidatorFlags::CanExtractEncryption
        );
        
        //Section 4.7 - Carriage of Track Roles
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'trackRoles'
        );
    }


    public function textComponentConstraints($representation)
    {
        return include 'impl/textComponentConstraints.php';
    }

    public function addressableMediaObject($representation)
    {
        return include 'impl/addressableMediaObject.php';
    }

    public function bitrate($representation)
    {
        return include 'impl/bitrate.php';
    }

    public function encryptionScheme($representation)
    {
        return include 'impl/encryptionScheme.php';
    }

    public function trackRoles($representation)
    {
        return include 'impl/trackRoles.php';
    }

    public function timedEventData($representation)
    {
        return include 'impl/timedEventData.php';
    }

    public function splicingPoints($representation)
    {
        return include 'impl/splicingPoints.php';
    }

    public function keyRotation($representation)
    {
        return include 'impl/keyRotation.php';
    }
}

$modules[] = new ModuleWaveHLSInterop();
