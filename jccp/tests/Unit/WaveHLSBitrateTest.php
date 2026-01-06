<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Wave\Segments\Bitrate;
use App\Services\ModuleReporter;
use App\Services\Segment;
use App\Services\Manifest\Representation;

enum BitRateTestCase
{
    case INVALID;
    case Valid1;
    case Valid2;
}


class MockSegment extends Segment
{
    private BitRateTestCase $case;
    public function __construct(BitRateTestCase $testCase)
    {
        $this->case = $testCase;
    }

    public function getSize(): int
    {
        switch ($this->case) {
            case BitRateTestCase::Valid1:
                return 1000;
            case BitRateTestCase::Valid2:
                return 2000;
        }
        return 0;
    }

    /**
     * @return array<float>
     **/
    public function getSegmentDurations(): array
    {
        switch ($this->case) {
            case BitRateTestCase::Valid1:
                return [1];
            case BitRateTestCase::Valid2:
                return [2];
        }
        return [];
    }
}

class WaveHLSBitrateTest extends TestCase
{
    /**
     * A basic unit test example.
     */

    private Representation $mockRepresentation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepresentation = new Representation(
            new \DOMElement("mockDOM"),
            0,
            0,
            0
        );
    }


    public function testExample(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();

        $this->assertEquals(count($reporter->knownContexts()), 1);
    }

    public function testValid(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, []);

        $this->assertEquals($reporter->verdict(), "PASS");
    }
}
