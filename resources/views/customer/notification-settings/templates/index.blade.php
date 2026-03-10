@extends('layouts.dashboard-minimal')

@section('title', 'E-Mail-Vorlagen - Benachrichtigungs-Einstellungen')

@php
    $active = 'notification-settings';
@endphp

@section('content')
    <div class="p-8">
        <div class="max-w-4xl mx-auto">
            {{-- Header --}}
            <div class="mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('customer.notification-settings.index') }}" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-file-alt mr-2"></i>
                        E-Mail-Vorlagen
                    </h1>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Anleitung --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Wozu dienen E-Mail-Vorlagen?</p>
                        <p>Vorlagen bestimmen das Aussehen Ihrer Benachrichtigungs-E-Mails. Die <strong>Standard-Vorlage</strong> (System) wird automatisch verwendet, wenn Sie keine eigene Vorlage erstellen. Sie können eigene Vorlagen erstellen und diese in Ihren Regeln zuweisen, um z.B. verschiedene Abteilungen mit unterschiedlich gestalteten E-Mails zu informieren.</p>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end mb-4">
                <a href="{{ route('customer.notification-settings.templates.create') }}"
                   class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-1"></i>
                    Neue Vorlage
                </a>
            </div>

            {{-- Templates List --}}
            <div class="space-y-3">
                @forelse($templates as $template)
                    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200 hover:border-gray-300 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-medium text-gray-900">{{ $template->name }}</h3>
                                    @if($template->is_system)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            <i class="fas fa-lock text-xs mr-1"></i>
                                            System
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-envelope mr-1"></i>
                                    Betreff: {{ $template->subject }}
                                </p>
                                @if($template->notificationRules && $template->notificationRules->count() > 0)
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-link mr-1"></i>
                                        Verwendet in {{ $template->notificationRules->count() }} {{ $template->notificationRules->count() === 1 ? 'Regel' : 'Regeln' }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex gap-2 ml-4">
                                @if(!$template->is_system)
                                    <a href="{{ route('customer.notification-settings.templates.edit', $template->id) }}"
                                       class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                        <i class="fas fa-edit"></i>
                                        Bearbeiten
                                    </a>
                                @else
                                    <span class="px-3 py-1.5 text-sm text-gray-400 cursor-not-allowed" title="System-Vorlagen können nicht bearbeitet werden">
                                        <i class="fas fa-lock"></i>
                                        Geschützt
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-lg shadow-sm p-8 border border-gray-200 text-center text-gray-500">
                        <i class="fas fa-file-alt text-3xl mb-3"></i>
                        <p>Keine Vorlagen vorhanden.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
