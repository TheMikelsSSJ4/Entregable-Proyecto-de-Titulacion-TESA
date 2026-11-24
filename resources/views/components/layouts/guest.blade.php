@props(['title'])

<!DOCTYPE html>
<html lang="es" data-tema="{{ $temaActual }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="{{ $temaActual === 'claro' ? 'light' : 'dark' }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-ui.tema class="fixed top-4 right-4 z-10 bg-grafito/80 backdrop-blur" />
    <main id="contenido" class="grid min-h-dvh place-items-center px-4 py-10">
        {{ $slot }}
    </main>
</body>
</html>
