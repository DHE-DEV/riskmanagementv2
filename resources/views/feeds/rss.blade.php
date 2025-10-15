{{--
    RSS 2.0 Feed Template

    This template generates a valid RSS 2.0 feed for events.

    Required variables:
    - $events: Collection of event models (CustomEvent or DisasterEvent)

    Optional variables:
    - $title: Feed title (default: site name)
    - $description: Feed description
    - $country: Country model for country-specific feeds
    - $link: Feed homepage link
--}}@include('feeds._header')
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        {{-- Channel metadata --}}
        <title>{{ $title ?? config('app.name') . ' - Events Feed' }}</title>
        <link>{{ $link ?? url('/') }}</link>
        <description>{{ $description ?? 'Latest events and updates from ' . config('app.name') }}</description>
        <language>de</language>
        <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
        <generator>{{ config('app.name') }}</generator>

        {{-- Atom self-link for feed discovery --}}
        <atom:link href="{{ url()->current() }}" rel="self" type="application/rss+xml" />

        @if(isset($country))
        {{-- Country-specific feed information --}}
        <category>{{ $country->name }}</category>
        @endif

        {{-- Feed items --}}
        @forelse($events as $event)
        <item>
            <title>{{ $event->title }}</title>

            {{-- Event link - adjust based on your routing --}}
            <link>{{ url('/events/' . $event->id) }}</link>

            {{-- Description with CDATA for HTML content --}}
            <description><![CDATA[{!! $event->description !!}]]></description>

            {{-- Publication date --}}
            <pubDate>{{ ($event->start_date ?? $event->event_date ?? $event->created_at)->toRfc2822String() }}</pubDate>

            {{-- Unique identifier --}}
            <guid isPermaLink="true">{{ url('/events/' . $event->id) }}</guid>

            {{-- Event categories --}}
            @if($event->eventType)
            <category>{{ $event->eventType->name }}</category>
            @endif

            @if(isset($event->country) && $event->country)
            <category>{{ $event->country->name }}</category>
            @endif

            @if(isset($event->severity))
            <category>{{ ucfirst($event->severity) }}</category>
            @endif

            @if(isset($event->priority))
            <category>{{ ucfirst($event->priority) }}</category>
            @endif
        </item>
        @empty
        {{-- Empty feed handling - no items when collection is empty --}}
        @endforelse
    </channel>
</rss>
