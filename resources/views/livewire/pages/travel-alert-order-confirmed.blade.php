@php
    /**
     * Landeseite fuer den Bestaetigungslink aus der Bestellmail.
     *
     * $state: activated | pending_approval | already_active | already_pending
     *         | expired | rejected | invalid
     */
    $content = match ($state) {
        'activated' => [
            'icon' => 'fa-circle-check',
            'tone' => 'emerald',
            'headline' => 'Bestellung bestätigt',
            'text' => 'Vielen Dank. Ihr Travel Alert-Zugang ist ab sofort freigeschaltet – Sie können direkt loslegen.',
        ],
        'pending_approval' => [
            'icon' => 'fa-circle-check',
            'tone' => 'amber',
            'headline' => 'Bestellung bestätigt',
            'text' => 'Vielen Dank. Ein Mitarbeiter prüft Ihre Bestellung und schaltet den Zugang frei. Sobald das erledigt ist, erhalten Sie eine E-Mail von uns.',
        ],
        'already_active' => [
            'icon' => 'fa-circle-info',
            'tone' => 'emerald',
            'headline' => 'Bereits bestätigt',
            'text' => 'Diese Bestellung haben Sie schon bestätigt, Ihr Zugang ist freigeschaltet.',
        ],
        'already_pending' => [
            'icon' => 'fa-circle-info',
            'tone' => 'amber',
            'headline' => 'Bereits bestätigt',
            'text' => 'Diese Bestellung haben Sie schon bestätigt. Die Freischaltung durch einen Mitarbeiter steht noch aus.',
        ],
        'expired' => [
            'icon' => 'fa-clock',
            'tone' => 'amber',
            'headline' => 'Link abgelaufen',
            'text' => 'Der Bestätigungslink ist nicht mehr gültig. Melden Sie sich kurz bei uns, dann schicken wir Ihnen einen neuen.',
        ],
        'rejected' => [
            'icon' => 'fa-circle-xmark',
            'tone' => 'rose',
            'headline' => 'Bestellung nicht aktiv',
            'text' => 'Diese Bestellung wurde nicht freigeschaltet. Bei Fragen dazu melden Sie sich gerne bei uns.',
        ],
        default => [
            'icon' => 'fa-circle-question',
            'tone' => 'rose',
            'headline' => 'Link unbekannt',
            'text' => 'Zu diesem Link konnten wir keine Bestellung finden. Bitte prüfen Sie, ob Sie ihn vollständig aus der E-Mail übernommen haben.',
        ],
    };

    $tones = [
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
        'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-600'],
    ];
    $tone = $tones[$content['tone']];
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $content['headline'] }} – Travel Alert</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    @endif
</head>
<body class="min-h-screen flex items-center justify-center p-6" style="background: #002742;">
    <div class="w-full max-w-lg rounded-2xl bg-white p-8 sm:p-10 text-center shadow-2xl">
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full {{ $tone['bg'] }}">
            <i class="fa-regular {{ $content['icon'] }} text-4xl {{ $tone['text'] }}"></i>
        </div>

        <h1 class="mb-3 text-2xl font-bold text-gray-900">{{ $content['headline'] }}</h1>

        <p class="mb-8 text-gray-500">{{ $content['text'] }}</p>

        @if(in_array($state, ['activated', 'already_active'], true))
            <a href="{{ route('risk-overview') }}"
               class="inline-flex items-center gap-2 rounded-xl px-6 py-3 font-semibold text-white transition-all"
               style="background: #002742;">
                <i class="fa-regular fa-arrow-right"></i>
                Zu Travel Alert
            </a>
        @else
            <a href="mailto:info@passolution.de"
               class="inline-flex items-center gap-2 rounded-xl px-6 py-3 font-semibold transition-all"
               style="background: #CEE741; color: #002742;">
                <i class="fa-regular fa-envelope"></i>
                info@passolution.de
            </a>
        @endif

        <p class="mt-8 text-xs text-gray-400">Passolution Travel Information Platform</p>
    </div>
</body>
</html>
