<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T7R2SWKD');</script>
<!-- End Google Tag Manager -->

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

{{-- RSS/Atom Feed Discovery --}}
<link rel="alternate" type="application/rss+xml" title="All Events (RSS)" href="{{ route('feed.events.all.rss') }}">
<link rel="alternate" type="application/atom+xml" title="All Events (Atom)" href="{{ route('feed.events.all.atom') }}">
<link rel="alternate" type="application/rss+xml" title="Critical Events (RSS)" href="{{ route('feed.events.priority', ['priority' => 'high']) }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
