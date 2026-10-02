<!DOCTYPE html>
<html lang="de">
    <head>
        @include('components.layouts.adminv2.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-900">
        <div class="grid min-h-svh lg:grid-cols-2">
            <div class="flex flex-col items-center justify-center p-6 md:p-10">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>

            <div class="relative hidden overflow-hidden bg-[#0b3250] lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div class="absolute -end-24 -top-24 size-96 rounded-full bg-[#c8dc3c]/20 blur-3xl"></div>
                <div class="absolute -bottom-32 -start-16 size-[28rem] rounded-full bg-sky-400/10 blur-3xl"></div>

                <div class="relative flex items-center gap-4 text-white">
                    <span class="flex shrink-0 items-center rounded-xl bg-white px-3 py-2.5 shadow-sm">
                        <img src="{{ asset('logo.png') }}" alt="" class="h-8 w-auto" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight">Passolution Travel Information Platform</span>
                </div>

                <div class="relative max-w-md">
                    <p class="text-3xl font-semibold leading-tight tracking-tight text-white">
                        Die Passolution Travel Information Platform verwalten.
                    </p>
                    <p class="mt-4 text-base text-white/70">
                        Der zentrale Admin-Bereich für Inhalte, Kunden und Einstellungen der Plattform.
                    </p>
                </div>

                <p class="relative text-sm text-white/50">Admin · {{ parse_url(config('app.url'), PHP_URL_HOST) }}</p>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
