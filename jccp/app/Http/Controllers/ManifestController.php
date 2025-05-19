<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\MPDHandler;
use App\Services\ModuleLogger;

class ManifestController extends Controller
{
    public function retrieve(Request $request) {

        $logger = app(ModuleLogger::class);
        $logger->validatorMessage("testmsg");

        //$url2  = "https://dash.akamaized.net/dash264/TestCasesUHD/2b/11/MultiRate.mpd";
        //$mpd = new MPDHandler($url2);


        /*
        return $logger->asJSON();
         */
        return view('mpd');
    }

    public function mpdJSON() {
        return app(ModuleLogger::class)->asJSON();

    }
}
