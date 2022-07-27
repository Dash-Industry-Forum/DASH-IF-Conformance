<?php

namespace DASHIF;

class ModuleDASH extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "MPEG-DASH Common";
        $this->enabled = true;
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("dash", "d", "dash", "Enable DASH-IF checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("dash")) {
            $this->enabled = true;
        }
    }


    public function hookMPD()
    {
        parent::hookMPD();
        global $logger;

        $this->validateMPD();
    }

    private function validateMPD()
    {
        global $session, $main_dir, $mpd_dom, $mpd_url, $mpd_log,
        $featurelist_log_html, $mpd_xml, $mpd_xml_string, $mpd_xml_report;

        global $logger;
        $schematronIssuesReport = null;

        $mpd_xml = simplexml_load_string($mpd_xml_string);
        $mpd_xml->asXml($session->getDir() . '/' . $mpd_xml_report);
        if (!$mpd_xml) {
            fwrite(STDERR, "Can't open $mpd_xml_string");
            return;
        }

        $result = $this->validateSchematron();

      ## Check the PASS/FAIL status
        $exit = false;

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($result, 'XLink resolving successful') !== false,
            "FAIL",
            "XLink resolving succesful",
            "XLink resolving failed"
        );

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($result, 'MPD validation successful') !== false,
            "FAIL",
            "MPD validation succesful",
            "MPD validation failed"
        );

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($result, 'Schematron validation successful') !== false,
            "FAIL",
            "Schematron validation succesful",
            "Schematron validation failed"
        );

      ## Featurelist generate
        if (strpos($result, 'Schematron validation successful') === false) {
            $schematronIssuesReport = analyzeSchematronIssues($mpdvalidator);
        }
      //createMpdFeatureList($mpd_dom, $schematronIssuesReport);
      //convertToHtml();
    }

    private function validateSchematron()
    {
        global $logger, $session, $mpd_url, $dash_schema_location, $mpd_xml_report;

        chdir('../DASH/mpdvalidator');
        $dash_schema_location = $this->findOrDownloadSchema();

        $mpdvalidator = syscall("java -cp \"saxon9he.jar:xercesImpl.jar:bin\" Validator \"" .
          explode('#', $mpd_url)[0] . "\" " . $session->getDir() .
          "/resolved.xml $dash_schema_location " . $session->getDir() . "/$mpd_xml_report");

        $result = extract_relevant_text($mpdvalidator);
        delete_schema($dash_schema_location);

        $logger->message("Schematron output: " . $result);
        chdir('../');

        return $result;
    }

    private function findOrDownloadSchema()
    {
        return include 'impl/findOrDownloadSchema.php';
    }
}

$modules[] = new ModuleDASH();
