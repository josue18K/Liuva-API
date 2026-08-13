<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response(<<<'HTML'
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="0;url=/app/"><title>Liuva</title></head>
<body><script>location.replace('/app/')</script><a href="/app/">Abrir Liuva</a></body></html>
HTML);
});

Route::get('/app/{path?}', function () {
    return response()->file(public_path('app/index.html'));
})->where('path', '.*');
