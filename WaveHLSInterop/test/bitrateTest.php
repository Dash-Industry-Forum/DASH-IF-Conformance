<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/ValidatorInterface.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';

enum BitRateTestCases
{
    case INVALID;
    case MismatchCounts;
    case ValidBitrates;
    case InvalidBitrates;
    case NoDuration;
}

class BitRateMockRepresentation extends DASHIF\RepresentationInterface
{
    private $case;
    public function __construct($testCase)
    {
        $this->case = $testCase;
    }

    public function getSegmentSizes()
    {
        $sizes = array();
        switch ($this->case) {
            case BitRateTestCases::MismatchCounts:
                $sizes[] = 1;
                $sizes[] = 2;
                break;
            case BitRateTestCases::ValidBitrates:
                $sizes[] = 1000;
                $sizes[] = 2000;
                break;
            case BitRateTestCases::InvalidBitrates:
                $sizes[] = 1000;
                $sizes[] = 3000;
                break;
            case BitRateTestCases::NoDuration:
                $sizes[] = 1000;
                $sizes[] = 2000;
                $sizes[] = 0;
                break;
        }
        return $sizes;
    }

    public function getSegmentDurations()
    {
        $durations = array();
        switch ($this->case) {
            case BitRateTestCases::MismatchCounts:
                $durations[] = 1;
                $durations[] = 2;
                $durations[] = 3;
                break;
            case BitRateTestCases::ValidBitrates:
                $durations[] = 1;
                $durations[] = 2;
                break;
            case BitRateTestCases::InvalidBitrates:
                $durations[] = 1;
                $durations[] = 2;
                break;
            case BitRateTestCases::NoDuration:
                $durations[] = 1;
                $durations[] = 2;
                $durations[] = 0;
                break;
        }
        return $durations;
    }
}

class BitRateMockValidator extends DASHIF\ValidatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Mock";
        $this->enabled = true;
        $this->flags[] = DASHIF\ValidatorFlags::PreservesOrder;
    }

    public function getRepresentation($p, $a, $r)
    {
      //We abuse $r to denote a testcase;
        return new BitRateMockRepresentation($r);
    }

    public function enableFeature($featureName)
    {
        throw new Exception("EnableFeature");
    }
    public function run($p, $a, $r)
    {
        throw new Exception("Run");
    }
}

class BitRateTestModule extends DASHIF\ModuleWaveHLSInterop
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class WaveBitRateTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['validatorWrapper'] = new DASHIF\ValidatorWrapper(false);
        $GLOBALS['validatorWrapper']->addValidator(new BitRateMockValidator());
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new BitRateTestModule();
    }


    public function testInvalid(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,BitRateTestCases::INVALID),
            $this->module,
            'bitrate'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testMismatchCounts(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,BitRateTestCases::MismatchCounts),
            $this->module,
            'bitrate'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testValidBitRates(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,BitRateTestCases::ValidBitrates),
            $this->module,
            'bitrate'
        );
        $this->assertEquals('PASS', $GLOBALS['logger']->asArray()['verdict']);
    }

    public function testInvalidBitRates(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,BitRateTestCases::InvalidBitrates),
            $this->module,
            'bitrate'
        );
        $this->assertEquals('FAIL', $GLOBALS['logger']->asArray()['verdict']);
    }
    public function testNoDuration(): void
    {
        $GLOBALS['validatorWrapper']->analyzeSingle(
            array(0,0,BitRateTestCases::NoDuration),
            $this->module,
            'bitrate'
        );
        $this->assertEquals('WARN', $GLOBALS['logger']->asArray()['verdict']);
    }
}
