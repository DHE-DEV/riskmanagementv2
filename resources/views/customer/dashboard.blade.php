@extends('layouts.public')

@section('title', 'Customer Dashboard - Global Travel Monitor')

@php
    $active = 'dashboard';
@endphp

@section('content')
    <div class="p-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-gray-900">
                        Willkommen, {{ auth('customer')->user()->name }}!
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Das ist Ihr persönliches Dashboard.
                    </p>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">
                            Profil
                        </h3>
                        <p class="text-sm text-blue-700">
                            {{ auth('customer')->user()->email }}
                        </p>
                        @if(auth('customer')->user()->isSocialLogin())
                            <p class="text-xs text-blue-600 mt-2">
                                Angemeldet mit: {{ ucfirst(auth('customer')->user()->provider) }}
                            </p>
                        @endif
                    </div>

                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <h3 class="text-lg font-semibold text-green-900 mb-2">
                            E-Mail Status
                        </h3>
                        <p class="text-sm text-green-700">
                            @if(auth('customer')->user()->hasVerifiedEmail())
                                <span class="inline-flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Verifiziert
                                </span>
                            @else
                                <span class="text-yellow-600">Nicht verifiziert</span>
                            @endif
                        </p>
                    </div>

                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <h3 class="text-lg font-semibold text-purple-900 mb-2">
                            Mitglied seit
                        </h3>
                        <p class="text-sm text-purple-700">
                            {{ auth('customer')->user()->created_at->format('d.m.Y') }}
                        </p>
                    </div>
                </div>

                <div class="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Profil vervollständigen
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Es fehlen nur noch wenige Schritte, bis Ihr Profil vollständig erfasst ist. Um den vollen Funktionsumfang nutzen zu können, führen Sie bitte die ausstehenden Aktionen aus.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="https://stage.global-travel-monitor.eu" class="block p-4 bg-white rounded-lg hover:shadow-md transition border border-gray-200">
                            <h3 class="font-semibold text-gray-900">Reisewarnungen</h3>
                            <p class="text-sm text-gray-600">Aktuelle Warnungen ansehen</p>
                        </a>
                        <a href="https://stage.global-travel-monitor.eu/entry-conditions" class="block p-4 bg-white rounded-lg hover:shadow-md transition border border-gray-200">
                            <h3 class="font-semibold text-gray-900">Einreisebestimmungen</h3>
                            <p class="text-sm text-gray-600">Bestimmungen prüfen</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
