<?php

namespace Tests\Unit\WaveHLSInterop\AddressableMediaObject;

use Tests\TestCase;
//
use App\Services\ModuleReporter;
use App\Services\Manifest\Representation;
//
use App\Modules\WaveHLSInterop\Segments\AddressableMediaObject;

class Test extends TestCase
{
    private Representation $mockRepresentation;
    private ModuleReporter $reporter;
    private AddressableMediaObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepresentation = new Representation(new \DOMElement("mockDOM"), 0, 0, 0);
        $this->reporter = app(ModuleReporter::class);
        $this->subject = new AddressableMediaObject();
    }

    public function testValid(): void
    {
        $this->subject->withSegment($this->mockRepresentation, new Segment(CaseEnum::Valid), 0);

        $this->assertEquals($this->reporter->verdict(), "PASS");
    }

    public function testNoSidx(): void
    {
        $this->subject->withSegment($this->mockRepresentation, new Segment(CaseEnum::NoSidx), 0);

        $this->assertEquals($this->reporter->verdict(), "FAIL");
    }
    public function testMultiSidx(): void
    {
        $this->subject->withSegment($this->mockRepresentation, new Segment(CaseEnum::MultiSidx), 0);

        $this->assertEquals($this->reporter->verdict(), "FAIL");
    }
    public function testSidxPost(): void
    {
        $this->subject->withSegment($this->mockRepresentation, new Segment(CaseEnum::SidxPost), 0);

        $this->assertEquals($this->reporter->verdict(), "FAIL");
    }
}
