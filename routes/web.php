<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/app/{path?}', function () {
    return response()->file(public_path('app/index.html'));
})->where('path', '.*');
