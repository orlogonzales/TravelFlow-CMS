<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — TravelFlow CMS (SPA Entrypoints)
|--------------------------------------------------------------------------
|
| Las rutas de administración y acceso público cargan el shell de la SPA en Vue 3.
| Las peticiones de API y Sanctum son despachadas prioritariamente por sus respectivos routers.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('welcome');
});

Route::get('/admin/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
