<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\MPDCache;
use App\Services\SpecManager;
use App\Services\SegmentManager;
use App\Services\Downloader;
use App\Services\ModuleReporter;
use App\Modules\DashIOP;
use App\Modules\DVB\MPD as DVBMpd;
//
use App\Services\Segment;

class ManifestDetails extends Component
{
    private mixed $results;

    private string $selectedSpec = '';

    private array $sections = ['MPD', 'Segments'];

    /**
     * @var array<Segment> $segmentDebug;
     **/
    private array $segmentDebug;

    public function __construct()
    {

        $specManager = app(SpecManager::class);
        $specManager->enable('HbbTV MPD Module');
        $specManager->enable('DVB Segments Module');
        //$specManager->enable('Wave HLS Interop Segments Module');
        $specManager->validate();

        $segmentManager = new SegmentManager();
        $this->segmentDebug = $segmentManager->getSegments(0, 0, 0);

        $specManager->validateSegments();

        $this->results = app(ModuleReporter::class)->serialize(true);
        if ($this->selectedSpec == '') {
            $allSpecs = $this->getSpecs();
            if (count($allSpecs) > 0) {
                $this->selectSpec($allSpecs[0]);
            }
        }
    }

    /**
     * This is a laravel-specific type, so we ignore it
     * @phpstan-ignore missingType.property
     **/
    protected $listeners = [
        'select-mpd' => '$refresh'
    ];

    public function selectSpec(string $spec): void
    {
        $this->selectedSpec = $spec;
    }

    public function render(): View
    {
        return view('livewire.manifest-details');
    }

    public function slugify(string $input): string
    {
        return Str::of($input)->slug('_');
    }

    public function getManifestChecks(): string
    {
        return '';
    }

    public function specManagerState(): string
    {
        return app(SpecManager::class)->stateJSON();
    }

    /**
     * @return array<string>
     **/
    public function getSpecs(): array
    {
        $keys = [];
        if (array_key_exists("MPD", $this->results)) {
            $keys = array_merge(
                $keys,
                array_keys($this->results["MPD"]),
            );
        }

        if (array_key_exists("Segments", $this->results)) {
            $keys =            array_merge(
                $keys,
                array_keys($this->results["Segments"])
            );
        }
        $keys = array_unique($keys);

        sort($keys);
        return $keys;
    }


    /**
     * @return array<string>
     **/
    public function getSections(string $spec, string $element): array
    {
        if (!array_key_exists($element, $this->results)) {
            return [];
        }
        return array_keys($this->results[$element][$spec]);
    }


    /**
     * @return mixed
     **/
    public function getResults(string $spec, string $element): mixed
    {
        return $this->results[$element][$spec];
    }

    public function getFeatures(): mixed
    {
        return \json_encode($this->segmentDebug, JSON_PRETTY_PRINT);
    }

    public function segmentUrls(): mixed
    {
        return null;
    }

    public function logs(): string
    {
        return '';
    }

    /**
     * @return array<array<string, mixed>>
     **/
    public function transformResults(string $spec, string $element): array
    {
        $res = [];
        if (!$spec) {
            return $res;
        }
        if (!array_key_exists($element, $this->results)) {
            return $res;
        }

        if (!array_key_exists($spec, $this->results[$element])) {
            return $res;
        }

        foreach ($this->results[$element][$spec] as $section => $sectionResults) {
            foreach ($sectionResults['checks'] as $check => $checkResults) {
                $res[] = [
                    'section' => $section,
                    'check' => $check,
                    'state' => $checkResults['state'],
                    'messages' => $checkResults['messages']
                ];
            }
        }

        usort(
            $res,
            function ($lhs, $rhs) {
                if ($lhs['state'] == $rhs['state']) {
                    return 0;
                }
                if ($lhs['state'] == 'FAIL') {
                    return -1;
                }
                if ($rhs['state'] == 'FAIL') {
                    return 1;
                }
                if ($lhs['state'] == 'WARN') {
                    return -1;
                }
                if ($rhs['state'] == 'WARN') {
                    return 1;
                }
                return 0;
            }
        );

        return $res;
    }
}
