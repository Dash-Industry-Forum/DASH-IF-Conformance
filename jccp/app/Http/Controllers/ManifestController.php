<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\MPDHandler;
use App\Services\ModuleLogger;

class ManifestController extends Controller
{
    protected ModuleLogger $logger;
    public function retrieve(Request $request, string $url): string {
        $logger = app(ModuleLogger::class);
        $logger->validatorMessage("testmsg");

        $url2  = "https://dash.akamaized.net/dash264/TestCasesUHD/2b/11/MultiRate.mpd";
        $mpd = new MPDHandler($url2);


        return $logger->asJSON();
    }
}
