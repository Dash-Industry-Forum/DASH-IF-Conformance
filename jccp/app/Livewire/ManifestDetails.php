<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Modules\DashIOP;
use Illuminate\View\View;
use App\Modules\DVB\MPD as DVBMpd;

class ManifestDetails extends Component
{
    private $mpdCache;
    private $reporter;
    private $results;

    public function __construct(){
        $this->mpdCache = app(MPDCache::class);
        $this->reporter = app(ModuleReporter::class);

        new DVBMpd()->validateMPD();
        $this->results = $this->reporter->serialize(true);

    }

    /**
     * This is a laravel-specific type, so we ignore it
     * @phpstan-ignore missingType.property
     **/
    protected $listeners = [
        'select-mpd' => '$refresh'
    ];

    public function render(): View
    {
        return view('livewire.manifest-details');
    }

    public function slugify(String $input){
        return Str::of($input)->slug('_');
    }

    public function getManifestChecks(): string
    {
        return '';
    }

    public function getSpecs(): array {
        return array_keys($this->results['MPD']);
    }
    public function getSections($spec): array {
        return array_keys($this->results['MPD'][$spec]);
    }

    public function getResults($spec): array {
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
}
