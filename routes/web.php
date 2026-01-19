<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ManifestController;

Route::get('/', function () {
    return view('home');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/statistics', function () {
    return view('statistics');
});

Route::get('/faq', function () {
    return view('faq');
});
Route::get('/terms', function () {
    return view('terms');
});

Route::get('/mpd', [ManifestController::class, 'retrieve']);

