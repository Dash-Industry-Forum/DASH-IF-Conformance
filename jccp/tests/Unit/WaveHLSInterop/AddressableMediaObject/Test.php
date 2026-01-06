<?php

namespace Tests\Unit\WaveHLSInterop\AddressableMediaObject;

use Tests\TestCase;
//
use App\Services\ModuleReporter;
use App\Services\Manifest\Representation;
//
use App\Modules\Wave\Segments\AddressableMediaObject;

class AddressableMediaObjectTest extends TestCase
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


    public function testConstruct(): void
    {
        $reporter = app(ModuleReporter::class);

        $addressable = new AddressableMediaObject();

        $this->assertEquals(count($reporter->knownContexts()), 1);
    }

    public function testValid(): void
    {
        $reporter = app(ModuleReporter::class);

        $addressable = new AddressableMediaObject();
        $addressable->validateAddressableMediaObject($this->mockRepresentation, new Segment(CaseEnum::Valid), 0);

        $this->assertEquals($reporter->verdict(), "PASS");
    }

    public function testNoSidx(): void
    {
        $reporter = app(ModuleReporter::class);

        $addressable = new AddressableMediaObject();
        $addressable->validateAddressableMediaObject($this->mockRepresentation, new Segment(CaseEnum::NoSidx), 0);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }
    public function testMultiSidx(): void
    {
        $reporter = app(ModuleReporter::class);

        $addressable = new AddressableMediaObject();
        $addressable->validateAddressableMediaObject($this->mockRepresentation, new Segment(CaseEnum::MultiSidx), 0);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }
    public function testSidxPost(): void
    {
        $reporter = app(ModuleReporter::class);

        $addressable = new AddressableMediaObject();
        $addressable->validateAddressableMediaObject($this->mockRepresentation, new Segment(CaseEnum::SidxPost), 0);

        $this->assertEquals($reporter->verdict(), "FAIL");
    }
}
