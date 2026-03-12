@extends('layouts.dashboard-minimal')

@section('title', 'Meine Ereignisse - Global Travel Monitor')

@php
    $active = 'customer-events';
@endphp

@section('content')
    <div class="p-8">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Meine Ereignisse
                    </h1>
                </div>
                <p class="text-sm text-gray-500 ml-10">Erstellen und verwalten Sie eigene Ereignisse für Ihr Unternehmen.</p>
            </div>

            @livewire('customer.customer-event-manager')
        </div>
    </div>
@endsection
