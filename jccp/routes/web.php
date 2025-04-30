<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ManifestController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/manifest/{url}', [ManifestController::class, 'retrieve']);

