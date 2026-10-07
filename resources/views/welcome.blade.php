<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="layout-navbar-fixed layout-menu-fixed layout-compact">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'TravelFlow CMS') }}</title>

        <!-- Fonts: Inter Oficial Local -->
        <link rel="stylesheet" href="/assets/vendor/fonts/inter/inter.css">

        <!-- Anti-Flicker: Aplicación inmediata del tema visual y layout previo a la hidratación -->
        <script>
            (function () {
                try {
                    var cached = localStorage.getItem('tf-ui-preferences');
                    var theme = 'system';
                    var collapsed = false;
                    if (cached) {
                        var parsed = JSON.parse(cached);
                        if (parsed.theme) theme = parsed.theme;
                        if (parsed.sidebar_collapsed) collapsed = !!parsed.sidebar_collapsed;
                    }
                    var resolved = theme;
                    if (theme === 'system') {
                        resolved = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    }
                    document.documentElement.setAttribute('data-bs-theme', resolved);
                    if (collapsed) {
                        document.documentElement.classList.add('layout-menu-collapsed');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Materialize Official Helpers (Layout calculation & navbar offset) -->
        <script src="/assets/vendor/js/helpers.js"></script>

        <!-- Vite Assets: Materialize v13.11.1 + Bootstrap 5.3.8 + Vue 3 + TypeScript -->
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
