<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('trs.GeneralEntryRequirements')) – Passolution Travel Information Platform</title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Tailwind (CDN, matching the project's public-page pattern) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        pds: {
                            blue: '#043451',
                            blue2: '#0C434A',
                            link: '#3973b9',
                            green: '#cee741',
                            tab: '#a8a8a8',
                        },
                    },
                    maxWidth: { 'card': '800px' },
                    borderRadius: { '2xl': '1rem', '3xl': '1.5rem' },
                },
            },
        };
    </script>

    <!-- Font Awesome -->
    @php $faKit = config('services.fontawesome.kit'); @endphp
    @if(!empty($faKit))
        <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true" onerror="window.__faKitOk=false"></script>
        <script>
        (function(){
            function addCss(href){ var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l); }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function(){ setTimeout(function(){ if(!window.__faKitOk){ addCss(fallbackHref); } }, 800); });
        })();
        </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    @endif

    <style>
        [x-cloak] { display: none !important; }
        body { margin: 0; background: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .pds-scroll::-webkit-scrollbar { width: 8px; }
        .pds-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        /* Rendered requirement HTML coming from the API */
        .pds-content { font-size: 0.95rem; line-height: 1.6; color: #374151; }
        .pds-content h1, .pds-content h2, .pds-content h3 { font-weight: 600; color: #1f2937; margin: 0.75rem 0 0.35rem; }
        .pds-content p { margin: 0 0 0.6rem; }
        .pds-content ul { list-style: disc; padding-left: 1.25rem; margin: 0 0 0.6rem; }
        .pds-content a { color: #3973b9; text-decoration: underline; }
        .pds-content table { width: 100%; border-collapse: collapse; margin: 0.5rem 0; }
        .pds-content td, .pds-content th { border: 1px solid #e5e7eb; padding: 0.35rem 0.5rem; }
    </style>
    @stack('head')
</head>
<body x-data="{ langOpen: false }">

@php
    $flagMap = ['de' => 'de', 'en' => 'gb', 'nl' => 'nl'];
    $navLocales = ['de', 'en', 'nl'];
    $activeLocale = $locale ?? app()->getLocale();
    $customer = auth('customer')->user();
@endphp

{{-- ============ TOP NAVBAR (pds-homepage style) ============ --}}
<nav class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex items-center justify-between h-[68px] gap-4">
            {{-- Brand --}}
            <a href="{{ route('travel-requirements-service-v2') }}" class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('logo.png') }}" alt="Passolution" class="h-9 w-auto">
                <span class="hidden md:inline text-lg font-light tracking-wide text-pds-blue">Passolution&nbsp;Travel&nbsp;Information&nbsp;Platform</span>
            </a>

            {{-- Right side --}}
            <div class="flex items-center gap-2 sm:gap-4">
                {{-- Help center --}}
                <a href="{{ route('help-center') }}" class="hidden sm:inline-flex items-center gap-2 text-sm text-pds-blue hover:opacity-70 transition">
                    <i class="fa-regular fa-circle-question"></i>
                    <span>{{ __('trs.HelpCenter') }}</span>
                </a>

                {{-- Language flags --}}
                <div class="flex items-center gap-1.5">
                    @foreach($navLocales as $lc)
                        <a href="{{ route('travel-requirements-service-v2', ['lang' => $lc]) }}"
                           title="{{ strtoupper($lc) }}"
                           class="inline-flex items-center justify-center rounded-sm overflow-hidden border {{ $activeLocale === $lc ? 'border-pds-blue ring-2 ring-pds-green' : 'border-gray-200 opacity-70 hover:opacity-100' }} transition">
                            <img src="https://flagcdn.com/24x18/{{ $flagMap[$lc] }}.png"
                                 srcset="https://flagcdn.com/48x36/{{ $flagMap[$lc] }}.png 2x"
                                 width="24" height="18" alt="{{ strtoupper($lc) }}">
                        </a>
                    @endforeach
                </div>

                {{-- Account --}}
                @auth('customer')
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                                class="flex items-center gap-2 pl-2 pr-1 py-1.5 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                            <span class="w-8 h-8 rounded-full bg-pds-blue text-white flex items-center justify-center text-sm font-semibold">
                                {{ strtoupper(substr($customer->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="hidden md:inline text-sm font-medium max-w-[160px] truncate">{{ $customer->name ?? $customer->email }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-52 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                            <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="fa-regular fa-gauge mr-2"></i>{{ __('trs.Dashboard') }}</a>
                            <a href="{{ route('customer.settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="fa-solid fa-gear mr-2"></i>{{ __('trs.Settings') }}</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <form method="POST" action="{{ route('customer.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50"><i class="fa-solid fa-right-from-bracket mr-2"></i>{{ __('trs.Logout') }}</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

@yield('content')

{{-- ============ FOOTER ============ --}}
<footer class="bg-gray-200 mt-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-10 text-center">
        <img src="{{ asset('logo.png') }}" alt="Passolution" class="h-10 w-auto mx-auto mb-4 opacity-80">
        <p class="text-sm text-gray-600">Passolution GmbH — Passolution Travel Information Platform</p>
        <p class="text-xs text-gray-500 mt-2">© {{ date('Y') }} Passolution GmbH. {{ __('trs.GeneralEntryRequirements') }}.</p>
    </div>
</footer>

@stack('scripts')
</body>
</html>
