@extends('layouts.dashboard-minimal')

@section('title', 'Versandprotokoll - Global Travel Monitor')

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
                        <i class="fas fa-history mr-2"></i>
                        Versandprotokoll
                    </h1>
                </div>
            </div>

            {{-- Hinweis --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Was zeigt das Versandprotokoll?</p>
                        <p>Hier sehen Sie alle E-Mail-Benachrichtigungen, die basierend auf Ihren Regeln versendet wurden. Der Status zeigt an, ob die Zustellung erfolgreich war. Bei fehlgeschlagenen Versendungen wird der Grund angezeigt.</p>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                @if($logs->isEmpty())
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-3"></i>
                        <p>Noch keine Benachrichtigungen versendet.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betreff</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empfänger</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regel</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($logs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                            {{ $log->created_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $log->subject }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $log->recipient_email }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $log->notificationRule?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                                            @if($log->status === 'sent')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle"></i>
                                                    Versendet
                                                </span>
                                            @elseif($log->status === 'failed')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" @if($log->error_message) title="{{ $log->error_message }}" @endif>
                                                    <i class="fas fa-times-circle"></i>
                                                    Fehlgeschlagen
                                                </span>
                                                @if($log->error_message)
                                                    <p class="text-xs text-red-600 mt-1">{{ $log->error_message }}</p>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    {{ $log->status }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

            {{-- Back link --}}
            <div class="mt-6">
                <a href="{{ route('customer.notification-settings.index') }}"
                   class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Zurück zu Benachrichtigungs-Einstellungen
                </a>
            </div>
        </div>
    </div>
@endsection
