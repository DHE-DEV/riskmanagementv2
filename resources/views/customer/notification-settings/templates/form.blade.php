@extends('layouts.dashboard-minimal')

@section('title', ($template ? 'Vorlage bearbeiten' : 'Neue Vorlage') . ' - Benachrichtigungs-Einstellungen')

@php
    $active = 'notification-settings';
@endphp

@section('content')
    <div class="p-8">
        <div class="max-w-3xl mx-auto">
            {{-- Header --}}
            <div class="mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('customer.notification-settings.templates.index') }}" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $template ? 'Vorlage bearbeiten' : 'Neue E-Mail-Vorlage' }}
                    </h1>
                </div>
            </div>

            <livewire:customer.notification-template-form :templateId="$template?->id" />
        </div>
    </div>
@endsection
