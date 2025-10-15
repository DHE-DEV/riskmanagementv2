{{--
    Atom 1.0 Feed Template

    This template generates a valid Atom 1.0 feed for events.

    Required variables:
    - $events: Collection of event models (CustomEvent or DisasterEvent)

    Optional variables:
    - $title: Feed title (default: site name)
    - $description: Feed subtitle/description
    - $country: Country model for country-specific feeds
    - $link: Feed homepage link
    - $authorName: Feed author name
    - $authorEmail: Feed author email
--}}@include('feeds._header')
<feed xmlns="http://www.w3.org/2005/Atom">
    {{-- Feed metadata --}}
    <title>{{ $title ?? config('app.name') . ' - Events Feed' }}</title>
    <subtitle>{{ $description ?? 'Latest events and updates from ' . config('app.name') }}</subtitle>

    {{-- Feed links --}}
    <link href="{{ url()->current() }}" rel="self" type="application/atom+xml" />
    <link href="{{ $link ?? url('/') }}" rel="alternate" type="text/html" />

    {{-- Feed identification --}}
    <id>{{ url()->current() }}</id>

    {{-- Last update time - use latest event or current time --}}
    <updated>{{ $events->isNotEmpty() ? $events->first()->updated_at->toIso8601String() : now()->toIso8601String() }}</updated>

    {{-- Generator information --}}
    <generator uri="{{ url('/') }}">{{ config('app.name') }}</generator>

    {{-- Author information --}}
    <author>
        <name>{{ $authorName ?? config('app.name') }}</name>
        @if(isset($authorEmail))
        <email>{{ $authorEmail }}</email>
        @endif
        <uri>{{ url('/') }}</uri>
    </author>

    @if(isset($country))
    {{-- Country-specific feed category --}}
    <category term="{{ $country->name }}" label="{{ $country->name }}" />
    @endif

    {{-- Feed entries --}}
    @forelse($events as $event)
    <entry>
        {{-- Entry title --}}
        <title>{{ $event->title }}</title>

        {{-- Entry links --}}
        <link href="{{ url('/events/' . $event->id) }}" rel="alternate" type="text/html" />

        {{-- Unique identifier --}}
        <id>{{ url('/events/' . $event->id) }}</id>

        {{-- Publication and update dates --}}
        <published>{{ ($event->created_at)->toIso8601String() }}</published>
        <updated>{{ ($event->updated_at ?? $event->created_at)->toIso8601String() }}</updated>

        {{-- Entry author --}}
        <author>
            <name>{{ $event->creator->name ?? $event->updater->name ?? config('app.name') }}</name>
        </author>

        {{-- Event summary/description --}}
        <summary type="html"><![CDATA[{!! $event->description !!}]]></summary>

        {{-- Entry categories --}}
        @if($event->eventType)
        <category term="{{ $event->eventType->code ?? 'event' }}" label="{{ $event->eventType->name }}" />
        @endif

        @if($event->eventTypes ?? false)
            @foreach($event->eventTypes as $eventType)
        <category term="{{ $eventType->code ?? 'event' }}" label="{{ $eventType->name }}" />
            @endforeach
        @endif

        @if(isset($event->country) && $event->country)
        <category term="{{ $event->country->iso2 ?? $event->country->name }}" label="{{ $event->country->name }}" />
        @endif

        @if(isset($event->countries) && $event->countries->isNotEmpty())
            @foreach($event->countries as $country)
        <category term="{{ $country->iso2 ?? $country->name }}" label="{{ $country->name }}" />
            @endforeach
        @endif

        @if(isset($event->severity))
        <category term="severity-{{ $event->severity }}" label="{{ ucfirst($event->severity) }} Severity" />
        @endif

        @if(isset($event->priority))
        <category term="priority-{{ $event->priority }}" label="{{ ucfirst($event->priority) }} Priority" />
        @endif

        @if(isset($event->event_type))
        <category term="{{ $event->event_type }}" label="{{ ucfirst($event->event_type) }}" />
        @endif
    </entry>
    @empty
    {{-- Empty feed handling - no entries when collection is empty --}}
    @endforelse
</feed>
