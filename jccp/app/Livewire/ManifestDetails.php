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
    private mixed $results;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);

        new DVBMpd()->validateMPD();
        $this->results = $reporter->serialize(true);
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

    public function slugify(string $input): string
    {
        return Str::of($input)->slug('_');
    }

    public function getManifestChecks(): string
    {
        return '';
    }

    /**
     * @return array<string>
     **/
    public function getSpecs(): array
    {
        return array_keys($this->results['MPD']);
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
}
