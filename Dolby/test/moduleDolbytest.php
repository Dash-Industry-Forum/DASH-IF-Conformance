<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';
require_once __DIR__ . '/../../Utils/boxes/boxes.php';
require_once __DIR__ . '/../../Utils/ValidatorWrapper.php';
require_once __DIR__ . '/../../Utils/MPDHandler.php';

enum DolbyTestCases
{
    case BothBoxesMissing;
    case OnlyDAC4Missing;
    case OnlyTOCMissing;
    case AllFieldsMatch;
    case BitstreamVersionMismatch;
}

class DolbyMockRepresentation extends \DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase) { $this->case = $testCase; }
    public function getDAC4Boxes(): array|null {
        switch ($this->case) {
            case DolbyTestCases::OnlyTOCMissing:
            case DolbyTestCases::AllFieldsMatch:
            case DolbyTestCases::BitstreamVersionMismatch:
                $dac4 = new \DASHIF\Boxes\DAC4();
                $dac4->bitstream_version = 1;
                $dac4->fs_index = 2;
                $dac4->frame_rate_index = 3;
                $dac4->n_presentations = 4;
                $dac4->short_program_id = 5;
                return [$dac4];
            default: return null;
        }
    }
    public function getAC4TOCBoxes(): array|null {
        switch ($this->case) {
            case DolbyTestCases::OnlyDAC4Missing:
                $toc = new \DASHIF\Boxes\AC4TOC();
                $toc->bitstream_version = 1;
                $toc->fs_index = 2;
                $toc->frame_rate_index = 3;
                $toc->n_presentations = 4;
                $toc->short_program_id = 5;
                return [$toc];
            case DolbyTestCases::AllFieldsMatch:
                $toc = new \DASHIF\Boxes\AC4TOC();
                $toc->bitstream_version = 1;
                $toc->fs_index = 2;
                $toc->frame_rate_index = 3;
                $toc->n_presentations = 4;
                $toc->short_program_id = 5;
                return [$toc];
            case DolbyTestCases::BitstreamVersionMismatch:
                $toc = new \DASHIF\Boxes\AC4TOC();
                $toc->bitstream_version = 99; // mismatch
                $toc->fs_index = 2;
                $toc->frame_rate_index = 3;
                $toc->n_presentations = 4;
                $toc->short_program_id = 5;
                return [$toc];
            default: return null;
        }
    }
}

class DolbyMockValidator extends \DASHIF\ValidatorInterface
{
    public function __construct() {
        parent::__construct();
        $this->name = "DolbyMock";
        $this->enabled = true;
    }
    public function getRepresentation($p, $a, $r) {
        // $r is the test case
        return new DolbyMockRepresentation($r);
    }
    public function enableFeature($featureName) { throw new \Exception("EnableFeature"); }
    public function run($p, $a, $r) { throw new \Exception("Run"); }
}

class MockMPDHandler extends \DASHIF\MPDHandler
{
    public function __construct($features)
    {
        parent::__construct(null); // No URL
        $this->setFeatures($features);
    }
}

class DolbyTestModule extends \DASHIF\ModuleDolby
{
    public function __construct() { parent::__construct(); }
}

final class ModuleDolbyTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new DolbyMockValidator());
        $GLOBALS['logger'] = new \DASHIF\ModuleLogger();

        $features = [
            'Period' => [
                0 => [
                    'AdaptationSet' => [
                        0 => [
                            'Representation' => [
                                0 => [
                                    'codecs' => 'ac-4',
                                    'mimeType' => 'audio/mp4'
                                ]
                            ],
                            'codecs' => 'ac-4',
                            'mimeType' => 'audio/mp4'
                        ]
                    ]
                ]
            ]
        ];
        $GLOBALS['mpdHandler'] = new MockMPDHandler($features);

        $this->module = new DolbyTestModule();
    }

    public function testBothBoxesMissing()
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,DolbyTestCases::BothBoxesMissing),
            $this->module,
            'validateDolby'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testOnlyDAC4Missing()
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,DolbyTestCases::OnlyDAC4Missing),
            $this->module,
            'validateDolby'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testOnlyTOCMissing()
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,DolbyTestCases::OnlyTOCMissing),
            $this->module,
            'validateDolby'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testAllFieldsMatch()
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,DolbyTestCases::AllFieldsMatch),
            $this->module,
            'validateDolby'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testBitstreamVersionMismatch()
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,DolbyTestCases::BitstreamVersionMismatch),
            $this->module,
            'validateDolby'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
}