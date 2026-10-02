<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="robots" content="noindex, nofollow" />

<title>{{ isset($title) ? $title.' · ' : '' }}Passolution Admin</title>

<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

{{-- Icons der Event-Typen (fa-…): Kit, falls konfiguriert, sonst die freie Version. --}}
@if ($faKit = config('services.fontawesome.kit'))
    <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous"></script>
@else
    <link rel="stylesheet" href="{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css' }}">
@endif

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

{{-- Passolution-Farben als Akzent: Dunkelblau im hellen, Limette im dunklen Modus. --}}
<style>
    [x-cloak] { display: none !important; }
    :root {
        --color-accent: #0b3250;
        --color-accent-content: #0b3250;
        --color-accent-foreground: #ffffff;
    }
    .dark {
        --color-accent: #c8dc3c;
        --color-accent-content: #c8dc3c;
        --color-accent-foreground: #0b3250;
    }
</style>
