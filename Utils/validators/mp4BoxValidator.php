<?php

namespace DASHIF;

require_once __DIR__ . '/mp4BoxRepresentation.php';

class MP4BoxValidator extends ValidatorInterface
{
    private $path;
    private $output;

    public function __construct()
    {
        parent::__construct();
        $this->name = "MP4BoxValidator";
        $this->enabled = (exec("MP4Box -h") !== false);
        $this->output = array();
        $this->flags[] = ValidatorFlags::PreservesOrder;
        $this->flags[] = ValidatorFlags::CanExtractEncryption;
    }


    public function run($period, $adaptation, $representation)
    {
        global $session, $mpdHandler;

        exec("MP4Box -dxml " . $session->getSelectedRepresentationDir() . "/assembled.mp4 2>&1", $this->output);

        $thisRep = new MP4BoxRepresentation();
        $thisRep->source = $this->name;
        $thisRep->periodNumber = $mpdHandler->getSelectedPeriod();
        $thisRep->adaptationNumber = $mpdHandler->getSelectedAdaptationSet();
        $thisRep->representationNumber = $mpdHandler->getSelectedRepresentation();
        $thisRep->payload = Utility\parseDom(
            $session->getSelectedRepresentationDir() . "/assembled_dump.xml",
            "ISOBaseMediaFileTrace"
        );


        $this->validRepresentations[] = $thisRep;

        $this->handleOutput();
    }

    private function handleOutput()
    {
        global $logger, $session, $mpdHandler;
        $representationDirectory = $session->getSelectedRepresentationDir();

        $currentModule = $logger->getCurrentModule();
        $currentHook = $logger->getCurrentHook();
        $logger->setModule("SEGMENT_VALIDATION");
        $logger->setHook("MP4BoxValidator");

        $contentArray = $this->output;

        $testName = "std error output for Period " . $mpdHandler->getSelectedPeriod() .
                    ", adaptation " . $mpdHandler->getSelectedAdaptationSet() .
                    ", representation " . $mpdHandler->getSelectedRepresentation();

        if (!count($contentArray)) {
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
                if (stripos($msg, "ignoring") !== false) {
//                    $severity = "WARN";
                }
                //Catch errors
                if (stripos($msg, "aborting") !== false) {
//                    $severity = "FAIL";
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
}
