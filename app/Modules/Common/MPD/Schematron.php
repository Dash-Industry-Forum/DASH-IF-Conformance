<?php

namespace App\Modules\Common\MPD;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use App\Interfaces\Module;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use Illuminate\Support\Facades\Log;

class Schematron
{
    private SubReporter $schematronReporter;
    private TestCase $schematronCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->schematronReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "Global",
            "Schematron",
            []
        ));

        $this->schematronCase = $this->schematronReporter->add(
            section: "Conformance Tool",
            test: "Schematron shall be able to run",
            skipReason: ''
        );
    }


    public function getSchematronOutput(): string
    {
        return $this->runSchematron();
    }


    private function runSchematron(): string
    {
        $sessionDir = session_dir();

        $validatorPath = base_path() . "/schematron";
        $schematronCommand = implode(" ", [
            "java",
            "-jar",
            "${validatorPath}/saxon12he.jar",
            "-versionmsg:off",
            "-s:${sessionDir}/manifest.mpd",
            "-o:${sessionDir}/schematron.xml",
            "-xsl:${validatorPath}/schematron/output/val_schema.xsl"
        ]);

        $schematronResult = Process::run($schematronCommand);


        $this->schematronCase->add(
            result: $schematronResult->successful(),
            severity: "FAIL",
            pass_message: "Schematron ran successfully",
            fail_message: "Unable to run schematron",
        );
        if (!$schematronResult->successful()) {
            $this->schematronCase->pathAdd(
                path: "stdout",
                result: false,
                severity: "INFO",
                pass_message: "",
                fail_message: $schematronResult->output()
            );
            $this->schematronCase->pathAdd(
                path: "stderr",
                result: false,
                severity: "INFO",
                pass_message: "",
                fail_message: $schematronResult->errorOutput()
            );
        }

        if (!$schematronResult->successful()) {
            return '';
        }

        return file_get_contents($sessionDir . "schematron.xml");
    }


    public function validateSchematron(): void
    {
        $schematronOutput = $this->getSchematronOutput();
        if (!$schematronOutput) {
            return;
        }

        $doc = new \DOMDocument();
        $doc->loadXML($schematronOutput);

        $namespace = 'http://purl.oclc.org/dsdl/svrl';
        $schematronResult = $doc->getElementsByTagNameNS($namespace, 'schematron-output')->item(0);
        $failedAssertions = $schematronResult->getElementsByTagNameNS($namespace, 'failed-assert');
        foreach ($failedAssertions as $failedAssertion) {
            $testLocation = $failedAssertion->getAttribute('location');
            $testDescription = $failedAssertion->getAttribute('test');
            $testRole = $failedAssertion->getAttribute('role');
            $textComponents = $failedAssertion->getElementsByTagNameNS($namespace, 'text');

            foreach ($textComponents as $textComponent) {
                //Always false, as we're parsing failed assertions
                $this->schematronReporter->add(
                    section: "Schematron",
                    test: $textComponent->nodeValue,
                    skipReason: ''
                )->add(
                    result: false,
                    severity: $testRole == "warn" ? "WARN" : "FAIL",
                    pass_message: "",
                    fail_message: "Test failed"
                );
            }
        }
    }
}
