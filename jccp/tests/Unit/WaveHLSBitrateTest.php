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
    case Invalid1;
    case Invalid2;
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
            case BitRateTestCase::Invalid1:
                return 2000;
            case BitRateTestCase::Invalid2:
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
            case BitRateTestCase::Invalid1:
                return [1];
            case BitRateTestCase::Invalid2:
                return [0];
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

    public function testNoRepresentations(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, []);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }

    public function testValidBitrates(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, [
            new MockSegment(BitRateTestCase::Valid1),
            new MockSegment(BitRateTestCase::Valid2),
        ]);

        $this->assertEquals($reporter->verdict(), "PASS");
    }

    public function testInvalidBitrates(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, [
            new MockSegment(BitRateTestCase::Valid1),
            new MockSegment(BitRateTestCase::Valid2),
            new MockSegment(BitRateTestCase::Invalid1),
        ]);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }

    public function testNoDuration(): void
    {
        $reporter = app(ModuleReporter::class);

        $bitrate = new Bitrate();
        $bitrate->validateBitrate($this->mockRepresentation, [
            new MockSegment(BitRateTestCase::Valid1),
            new MockSegment(BitRateTestCase::Valid2),
            new MockSegment(BitRateTestCase::Invalid2),
        ]);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }
}
