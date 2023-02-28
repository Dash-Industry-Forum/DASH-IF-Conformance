<?php

namespace DASHIF;

class ISOSegmentValidatorRepresentation extends RepresentationInterface
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getHandlerType(){
      if (!$this->payload){return null;}
      $handlerBoxes = $this->payload->getElementsByTagName('hdlr');
      if (count($handlerBoxes) == 0){return null;}
      return $handlerBoxes->item(0)->getAttribute('handler_type');
    }

    public function getSDType(){
      if (!$this->payload){return null;}
      $handlerType = $this->getHandlerType();
      $sampleDescriptionBoxes = $this->payload->getElementsByTagName($handlerType. "_sampledescription");
      if (count($sampleDescriptionBoxes) == 0){return null;}
      return $sampleDescriptionBoxes->item(0)->getAttribute('sdType');
    }

    public function getTrackId($boxName, $index){
      if (!$this->payload){return null;}
      $boxes = array();
      switch ($boxName){
        case 'TKHD':
          $boxes = $this->payload->getElementsByTagName('tkhd');
          break;
        case 'TFHD':
          $boxes = $this->payload->getElementsByTagName('tfhd');
          break;
        default:
          return null;
      }
      if (count($boxes) <= $index){return null;}
      return $boxes->item($index)->getAttribute('trackID');
    }

    public function getWidth(){
      if (!$this->payload){return null;}
      $handlerType = $this->getHandlerType();
      if ($handlerType != 'vide'){return null;}
      $sampleDescriptionBoxes = $this->payload->getElementsByTagName($handlerType. "_sampledescription");
      if (count($sampleDescriptionBoxes) == 0){return null;}
      return $sampleDescriptionBoxes->item(0)->getAttribute('Width');
    }
    public function getHeight(){
      if (!$this->payload){return null;}
      $handlerType = $this->getHandlerType();
      if ($handlerType != 'vide'){return null;}
      $sampleDescriptionBoxes = $this->payload->getElementsByTagName($handlerType. "_sampledescription");
      if (count($sampleDescriptionBoxes) == 0){return null;}
      return $sampleDescriptionBoxes->item(0)->getAttribute('Height');
    }

    public function getDefaultKID(){
      if (!$this->payload){return null;}
      $tencBoxes = $this->payload->getElementsByTagName("tenc");
      if (count($tencBoxes) == 0){return null;}
      return $tencBoxes->item(0)->getAttribute('default_KID');
    }

    public function hasBox($boxName){
      if (!$this->payload){return false;}
      $boxes = array();
      switch ($boxName){
        case 'TENC':
          $boxes = $this->payload->getElementsByTagName('tenc');
          break;
        default:
          return null;
      }
      if (count($boxes) == 0){return false;}
      return true;
    }

    public function getRawBox($boxName, $index){
      if (!$this->payload){return null;}
      $boxes = array();
      switch ($boxName){
        case 'STSD':
          $boxes = $this->payload->getElementsByTagName('stsd');
          break;
        default:
          return null;
      }
      if (count($boxes) <= $index){return null;}
      return $boxes->item($index);
    }

}

class ISOSegmentValidator extends ValidatorInterface
{
    private $isDolby;
    private $suppressatomlevel;

    public function __construct()
    {
        parent::__construct();
        $this->name = "ISOSegmentValidator";
        $this->enabled = true;
        $this->isDolby = false;
        $this->suppressAtomLevel = false;
    }

    public function enableFeature($featureName)
    {
        switch ($featureName) {
            case 'Dolby':
                $this->isDolby = true;
                break;
            case 'SuppressAtomLevel':
                $this->supressAtomLevel = true;
                break;
        }
    }

    private function runHealthChecks()
    {
        global $session, $logger, $mpdHandler;

        $sessionDirectory = $session->getDir();

        $moveAtom = true;

        $currentModule = $logger->getCurrentModule();
        $currentHook = $logger->getCurrentHook();

        $logger->setModule("HEALTH");
        $logger->setHook("ISOSegmentValidator");
        $moveAtom &= $logger->test(
            "Health Checks",
            "Segment Validation",
            "ISOSegmentValidator runs successful",
            $returncode == 0,
            "FAIL",
            "Ran succesful on $configFile; took " . ($et - $t) . "seconds",
            "Issues with $configFile; Returncode $returncode; took " . ($et - $t) . " seconds"
        );

        $moveAtom &= $logger->test(
            "Health Checks",
            "Segment Validation",
            "AtomInfo written",
            file_exists("$sessionDirectory/atominfo.xml"),
            "FAIL",
            "Atominfo for $representationDirectory exists",
            "Atominfo for $representationDirectory missing"
        );

        $atomXmlString = file_get_contents("$sessionDirectory/atominfo.xml");
        $STYPBeginPos = strpos($atomXmlString, "<styp");
        if ($STYPBugPos !== false) {
          //try with newline for prettyprinted
            $emptyCompatBrands = strpos($atomXmlString, "compatible_brands='[\n  </styp>", $STYPBeginPos);
            if ($emptyCompatBrands === false) {
            //Also try without newline just to be sure
                $emptyCompatBrands = strpos($atomXmlString, "compatible_brands='[</styp>", $STYPBeginPos);
            }
            if ($emptyCompatBrands !== false) {
                $logger->message(
                    "Fixed empty styp xml bug for period " . $mpdHandler->getSelectedPeriod() . " adaptation " .
                    $mpdHandler->getSelectedAdaptationSet() . " representation " . $mpdHandler->getSelectedRepresentation()
                );
                $fixedAtom = substr_replace($atomXmlString, "]'>", $emptyCompatBrands + 20, 0);
                file_put_contents("$sessionDirectory/atominfo.xml", $fixedAtom);
            }
        }

        $xml = Utility\parseDOM("$sessionDirectory/atominfo.xml", 'atomlist');
        $moveAtom &= $logger->test(
            "Health Checks",
            "Segment Validation",
            "AtomInfo contains valid xml",
            $xml !== false,
            "FAIL",
            "Atominfo for $representationDirectory has valid xml",
            "Atominfo for $representationDirectory has invalid xml"
        );

        $moveAtom &= $logger->test(
            "Health Checks",
            "Segment Validation",
            "AtomInfo < 100Mb",
            filesize("$sessionDirectory/atominfo.xml") < (100 * 1024 * 1024),
            "FAIL",
            "Atominfo for $representationDirectory < 100Mb",
            "Atominfo for $representationDirectory is " . filesize("$sessionDirectory/atominfo.xml")
        );
        // Restore module information since health checks are over
        $logger->setModule($currentModule);
        $logger->setHook($currentHook);

        //Return the healthyness of the atomInfo file
        return $moveAtom;
    }

    private function runValidator()
    {
        global $session, $logger, $mpdHandler;

        $sessionDirectory = $session->getDir();

        $representationDirectory = $session->getSelectedRepresentationDir();

    ## Select the executable version
    ## Copy segment validation tool to session folder
        $validatemp4 = __DIR__ . '/../../ISOSegmentValidator/public/linux/bin/ValidateMP4.exe';
        chmod("$validatemp4", 0777);

    ## Execute backend conformance software
        $command = "timeout -k 30s 30s $validatemp4 -logconsole -atomxml -configfile $representationDirectory/isoSegmentValidatorConfig.txt";
        $output = [];
        $returncode = 0;
        chdir($sessionDirectory);


        $t = time();
        exec($command, $output, $returncode);
        $et = time();

        $isHealthy = $this->runHealthChecks();


        if (!$isHealthy) {
            fwrite(STDERR, "Ignoring atomfile for $representationDirectory\n");
            if ($representationDirectory != "") {
                rename("$sessionDirectory/atominfo.xml", "$representationDirectory/errorAtomInfo.xml");
            }
        } else {
            if ($representationDirectory != "") {
                rename("$sessionDirectory/atominfo.xml", "$representationDirectory/atomInfo.xml");
            }
        }

        $thisRep = new ISOSegmentValidatorRepresentation();
        $thisRep->source = $this->name;
        $thisRep->periodNumber = $mpdHandler->getSelectedPeriod();
        $thisRep->adaptationNumber = $mpdHandler->getSelectedAdaptationSet();
        $thisRep->representationNumber = $mpdHandler->getSelectedRepresentation();
        $thisRep->payload = Utility\parseDom($session->getSelectedRepresentationDir() . "/atomInfo.xml", "atomlist");


        $this->validRepresentations[] = $thisRep;
    }

    private function handleOutput()
    {
        global $logger, $session, $mpdHandler;
        $representationDirectory = $session->getSelectedRepresentationDir();

        $currentModule = $logger->getCurrentModule();
        $currentHook = $logger->getCurrentHook();
        $logger->setModule("SEGMENT_VALIDATION");
        $logger->setHook("ISOSegmentValidator");


        $testName = "std error output for Period " . $mpdHandler->getSelectedPeriod() . 
                    ", adaptation " . $mpdHandler->getSelectedAdaptationSet() . 
                    ", representation " . $mpdHandler->getSelectedRepresentation();

        $content = file_get_contents("$representationDirectory/stderr.txt");
        $contentArray = explode("\n", $content);

        if (!count($contentArray)){
            $logger->test(
                "Segment Validation",
                "Segment Validation",
                $testName,
                true,
                "PASS",
                "Segment validation did not produce any output",
                $content
            );
        } else {
            foreach ($contentArray as $i => $msg) {
                $severity = "PASS";
                //Catch both warn and warning
                if (stripos($msg, "warn") !== false) {
                    $severity = "WARN";
                }
                //Catch errors
                if (stripos($msg, "error") !== false) {
                    $severity = "FAIL";
                }

                $logger->test(
                    "Segment Validation",
                    "Segment Validation",
                    $testName,
                    $severity == "PASS",
                    $severity,
                    $msg,
                    $msg
                );
            }
        }


    // Restore module information since health checks are over
        $logger->setModule($currentModule);
        $logger->setHook($currentHook);
    }

    public function run($period, $adaptation, $representation)
    {
        $this->createConfigFile($period, $adaptation, $representation);
        $this->runValidator();
        $this->handleOutput();
    }

    private function createConfigFile($period, $adaptation, $representation)
    {
        global $additional_flags, $suppressatomlevel, $hls_manifest, $session;

        $representationDirectory = $session->getSelectedRepresentationDir();
        $file = fopen("$representationDirectory/isoSegmentValidatorConfig.txt", 'w');
        fwrite($file, "$representationDirectory/assembled.mp4 \n");
        fwrite($file, "-infofile" . "\n");
        fwrite($file, "$representationDirectory/assemblerInfo.txt \n");

        if (!$this->isDolby) {
            fwrite($file, "-offsetinfo" . "\n");
            fwrite($file, "$representationDirectory/mdatoffset \n");
        }

        $flags = (!$hls_manifest) ? construct_flags(
            $period,
            $adaptation,
            $representation
        ) . $additional_flags : $additional_flags;
        $piece = explode(" ", $flags);
        foreach ($piece as $pie) {
            if ($pie !== "") {
                fwrite($file, $pie . "\n");
            }
        }
        if ($this->suppressAtomLevel) {
            fwrite($file, '-suppressatomlevel' . "\n");
        }

        fclose($file);
    }
}

global $validators;
$validators[] = new ISOSegmentValidator();