<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'TravelFlow CMS') }}</title>

        <!-- Vite Assets: Bootstrap 5.3.8 + Vue 3 + TypeScript -->
        @vite(['resources/scss/app.scss', 'resources/js/app.ts'])
    </head>
    <body>
        <div id="app">
            <div class="container py-5 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando TravelFlow CMS...</span>
                </div>
            </div>
        </div>
    </body>
</html>
