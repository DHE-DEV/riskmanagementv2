{{-- Eine Anleitung aus docs/*.md, gerendert durch App\Services\ApiDocRenderer. --}}
@extends('docs.api.layout')

@section('title', $guide['title'])
@section('api_color', $guide['color'])

@section('sidebar')
    <span class="sidebar-heading">{{ $guide['title'] }}</span>
    @foreach ($toc as $item)
        @if ($item['level'] === 2)
            <a href="#{{ $item['id'] }}">{{ $item['text'] }}</a>
        @elseif ($item['level'] === 3)
            <a href="#{{ $item['id'] }}" style="padding-left: 36px; font-size: 0.8rem;">{{ $item['text'] }}</a>
        @endif
    @endforeach

    <span class="sidebar-heading">Downloads</span>
    <a href="/docs/{{ $guide['file'] }}"><i class="fas fa-file-alt text-xs mr-1"></i> Anleitung (Markdown)</a>
    @if ($guide['openapi'])
        <a href="/docs/{{ $guide['openapi'] }}"><i class="fas fa-file-code text-xs mr-1"></i> OpenAPI (YAML)</a>
    @endif
@endsection

@section('content')
    {!! $html !!}
@endsection
