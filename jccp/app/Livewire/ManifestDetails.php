<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Services\MPDCache;
use App\Services\SpecManager;
use App\Services\ModuleReporter;
use App\Modules\DashIOP;
use Illuminate\View\View;
use App\Modules\DVB\MPD as DVBMpd;


class ManifestDetails extends Component
{
    private mixed $results;

    private string $selectedSpec = '';

    public function __construct()
    {

        $specManager = app(SpecManager::class);
        $specManager->enable('HbbTV MPD Module');
        $specManager->validate();
        $this->results = app(ModuleReporter::class)->serialize(true);
        if ($this->selectedSpec == ''){
            $this->selectSpec($this->getSpecs()[0]);
        }
    }

    /**
     * This is a laravel-specific type, so we ignore it
     * @phpstan-ignore missingType.property
     **/
    protected $listeners = [
        'select-mpd' => '$refresh'
    ];

    public function selectSpec(string $spec):void {
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
        $keys = array_keys($this->results['MPD']);
        sort($keys);
        return $keys;
    }


    /**
     * @return array<string>
     **/
    public function getSections(string $spec): array
    {
        return array_keys($this->results['MPD'][$spec]);
    }


    /**
     * @return mixed
     **/
    public function getResults(string $spec): mixed
    {
        return $this->results['MPD'][$spec];
    }

    public function getFeatures(): mixed
    {
        return \json_encode($this->results, JSON_PRETTY_PRINT);
    }

    public function segmentUrls(): mixed
    {
        return null;
    }

    public function logs(): string
    {
        return '';
    }

    public function transformResults(string $spec): array
    {
        $res = [];
        if (!$spec){
            return $res;
        }

        foreach ($this->results['MPD'][$spec] as $section => $sectionResults) {
            foreach ($sectionResults['checks'] as $check => $checkResults) {
                $res[] = [
                    'section' => $section,
                    'check' => $check,
                    'state' => $checkResults['state'],
                    'messages' => $checkResults['messages']
                ];
            }
        }

        usort($res,
function ($lhs, $rhs) {
    if ($lhs['state'] == $rhs['state']){
        return 0;
    }
    if ($lhs['state'] == 'FAIL'){
        return -1;
    }
    if ($rhs['state'] == 'FAIL'){
        return 1;
    }
    if ($lhs['state'] == 'WARN'){
        return -1;
    }
    if ($rhs['state'] == 'WARN'){
        return 1;
    }
    return 0;

});

        return $res;
    }
}
