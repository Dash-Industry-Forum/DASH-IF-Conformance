<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../ValidatorWrapper.php';
require_once __DIR__ . '/../ValidatorInterface.php';

class MockValidator extends DASHIF\ValidatorInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Mock";
        $this->enabled = true;
    }

    public function getRepresentation($p, $a, $r)
    {
        return $p == 1;
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

class MockAnalyzer
{
    public function analyzeSingle($representation)
    {
        return $representation;
    }
}

final class ValidatorWrapperTest extends TestCase
{
    public function testGlobalConstruction(): void
    {
        $validatorWrapper = $GLOBALS['validatorWrapper'];
        $this->assertNotNull($validatorWrapper);
    }

    public function testDefaultConstruction()
    {
        $validatorWrapper = new DASHIF\ValidatorWrapper();
        $this->assertTrue($validatorWrapper->hasValidator("MP4BoxValidator"));
        $this->assertTrue($validatorWrapper->hasValidator("ISOSegmentValidator"));
    }
    public function testEmptyConstruction()
    {
        $validatorWrapper = new DASHIF\ValidatorWrapper(false);
        $this->assertFalse($validatorWrapper->hasValidator("MP4BoxValidator"));
        $this->assertFalse($validatorWrapper->hasValidator("ISOSegmentValidator"));
    }

    public function testAnalyzeSingleDispatch(): void
    {
        $validatorWrapper = new DASHIF\ValidatorWrapper(false);
        $this->assertNotNull($validatorWrapper);

        $mock = new MockAnalyzer();
        $this->assertNull($validatorWrapper->analyzeSingle(array(), $mock, 'analyzeSingle'));
        $this->assertNull($validatorWrapper->analyzeSingle(array(1,2,3), $mock, 'analyzeSingle'));
        $validatorWrapper->addValidator(new DASHIF\ValidatorInterface());
        $this->assertNull($validatorWrapper->analyzeSingle(array(1,2,3), $mock, 'analyzeSingle'));

        $v = new MockValidator();
        $validatorWrapper->addValidator($v);
        $this->assertTrue($validatorWrapper->analyzeSingle(array(1,2,3), $mock, 'analyzeSingle'));
        $this->assertNull($validatorWrapper->analyzeSingle(array(0,1,2), $mock, 'analyzeSingle'));

      //Flag detection
        $this->assertNull($validatorWrapper->analyzeSingle(
            array(1,2,3),
            $mock,
            'analyzeSingle',
            [DASHIF\ValidatorFlags::PreservesOrder]
        ));
    }

    public function testEnableFeatureDispatch(): void
    {
        $validatorWrapper = new DASHIF\ValidatorWrapper(false);
        $validatorWrapper->addValidator(new DASHIF\ValidatorInterface());
        $validatorWrapper->addValidator(new MockValidator());

        $this->expectException(Exception::class);

        $validatorWrapper->enableFeature('SomeFeature');
    }

    public function testRunDispatch(): void
    {
        $validatorWrapper = new DASHIF\ValidatorWrapper(false);
        $validatorWrapper->addValidator(new DASHIF\ValidatorInterface());
        $validatorWrapper->addValidator(new MockValidator());

        $this->expectException(Exception::class);

        $validatorWrapper->run(1, 2, 3);
    }

    public function testPrintEnabled(): void
    {
        $validatorWrapper = new DASHIF\ValidatorWrapper();
        $validatorWrapper->printEnabled();

        $testOutput = $this->getActualOutput();
        $this->assertTrue(str_contains($testOutput, "is enabled?"));
    }
}
