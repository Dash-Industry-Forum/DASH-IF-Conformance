<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

use App\Services\MPDHandler;

class AnalyzeManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:analyze-manifest {manifest_url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze a manifest';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        session(['artisan' => true]);
        $url = Cache::remember(cache_path(['url']), 60, function(){
            return $this->argument('manifest_url');
        });
        $mpd = Cache::remember(cache_path(['mpd']), 60, function(){
            return file_get_contents(Cache::get(cache_path(['url'])));
        });
        #echo "$mpd \n";
    }
}
