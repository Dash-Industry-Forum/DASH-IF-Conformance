<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\MPDHandler;
use App\Services\ModuleLogger;

class ManifestController extends Controller
{
    public function retrieve(Request $request): View
    {

        $logger = app(ModuleLogger::class);
        $logger->validatorMessage("testmsg");

        return view('mpd');
    }

    public function mpdJSON(): string
    {
        return app(ModuleLogger::class)->asJSON();
    }
}
