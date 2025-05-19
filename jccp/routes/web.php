<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ManifestController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mpd', [ManifestController::class, 'retrieve']);

