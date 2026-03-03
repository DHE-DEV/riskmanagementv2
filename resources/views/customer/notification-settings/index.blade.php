@extends('layouts.dashboard-minimal')

@section('title', 'Benachrichtigungs-Einstellungen - Global Travel Monitor')

@php
    $active = 'notification-settings';
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
                        <i class="fas fa-bell mr-2"></i>
                        Benachrichtigungs-Einstellungen
                    </h1>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Global Toggle --}}
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Globale Einstellungen</h2>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Automatische Benachrichtigungen</p>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Diese Einstellungen gelten für alle kommenden und laufenden Reisen.
                            Wenn aktiviert, werden E-Mails basierend auf Ihren Regeln versendet.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('customer.notification-settings.toggle') }}">
                        @csrf
                        <button type="submit" class="relative inline-flex items-center cursor-pointer">
                            <div class="w-11 h-6 {{ $customer->notifications_enabled ? 'bg-blue-600' : 'bg-gray-200' }} rounded-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all {{ $customer->notifications_enabled ? 'after:translate-x-full after:border-white' : '' }}"></div>
                        </button>
                    </form>
                </div>
                <div class="mt-3">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $customer->notifications_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        <i class="fas {{ $customer->notifications_enabled ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                        {{ $customer->notifications_enabled ? 'Aktiviert' : 'Deaktiviert' }}
                    </span>
                </div>
            </div>

            {{-- Rules List --}}
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-list-check mr-2"></i>
                        Benachrichtigungs-Regeln
                    </h2>
                    <a href="{{ route('customer.notification-settings.rules.create') }}"
                       class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-1"></i>
                        Neue Regel
                    </a>
                </div>

                @if($rules->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-3"></i>
                        <p>Noch keine Regeln erstellt.</p>
                        <p class="text-sm mt-1">Erstellen Sie eine Regel, um Benachrichtigungen zu erhalten.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($rules as $rule)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h3 class="font-medium text-gray-900">{{ $rule->name }}</h3>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $rule->is_active ? 'aktiv' : 'inaktiv' }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                                            @if($rule->risk_levels)
                                                <span>
                                                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                                    Stufen: {{ implode(', ', $rule->risk_level_labels) }}
                                                </span>
                                            @endif
                                            @if($rule->categories)
                                                <span>
                                                    <i class="fas fa-tag text-blue-500 mr-1"></i>
                                                    Kat: {{ implode(', ', $rule->category_labels) }}
                                                </span>
                                            @endif
                                            @if($rule->country_ids)
                                                <span>
                                                    <i class="fas fa-globe text-green-500 mr-1"></i>
                                                    {{ count($rule->country_ids) }} {{ count($rule->country_ids) === 1 ? 'Land' : 'Länder' }}
                                                </span>
                                            @else
                                                <span>
                                                    <i class="fas fa-globe text-green-500 mr-1"></i>
                                                    Alle Länder
                                                </span>
                                            @endif
                                            <span>
                                                <i class="fas fa-envelope text-purple-500 mr-1"></i>
                                                {{ $rule->recipients->count() }} Empfänger
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex gap-2 ml-4">
                                        <a href="{{ route('customer.notification-settings.rules.edit', $rule->id) }}"
                                           class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                            <i class="fas fa-edit"></i>
                                            Bearbeiten
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Templates Section --}}
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-file-alt mr-2"></i>
                            E-Mail-Vorlagen
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $customTemplateCount }} eigene {{ $customTemplateCount === 1 ? 'Vorlage' : 'Vorlagen' }}, {{ $systemTemplateCount }} System-{{ $systemTemplateCount === 1 ? 'Vorlage' : 'Vorlagen' }}
                        </p>
                    </div>
                    <a href="{{ route('customer.notification-settings.templates.index') }}"
                       class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-cog mr-1"></i>
                        Vorlagen verwalten
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
