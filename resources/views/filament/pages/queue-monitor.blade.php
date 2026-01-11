<x-filament-panels::page>
    {{-- Worker Status Banner --}}
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; margin-bottom: 1.5rem; border-radius: 12px; {{ $workerStatus['is_running'] ? 'background: #dcfce7; border: 1px solid #86efac;' : 'background: #fef3c7; border: 1px solid #fcd34d;' }}">
        <div style="display: flex; align-items: center; gap: 12px;">
            @if($workerStatus['is_running'])
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite;"></div>
                <span style="font-weight: 600; color: #166534;">Worker aktiv</span>
                @if($workerStatus['processing'] > 0)
                    <span style="font-size: 0.875rem; color: #15803d;">{{ $workerStatus['processing'] }} Job(s) werden verarbeitet</span>
                @endif
            @else
                <div style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b;"></div>
                <span style="font-weight: 600; color: #92400e;">Worker inaktiv</span>
                <span style="font-size: 0.875rem; color: #a16207;">Jobs werden nicht automatisch verarbeitet</span>
            @endif
        </div>
        <div style="display: flex; gap: 8px;">
            @if($stats['pending'] > 0)
                <button
                    wire:click="processNextJob"
                    style="display: inline-flex; align-items: center; gap: 6px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 16px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.15s;"
                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#9ca3af'"
                    onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                    </svg>
                    Nächsten Job
                </button>
                <button
                    wire:click="processAllJobs"
                    wire:confirm="Alle {{ $stats['pending'] }} Jobs jetzt verarbeiten? Dies kann einige Zeit dauern."
                    style="display: inline-flex; align-items: center; gap: 6px; background: #2563eb; color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: background 0.15s;"
                    onmouseover="this.style.background='#1d4ed8'"
                    onmouseout="this.style.background='#2563eb'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061A1.125 1.125 0 0 1 3 16.811V8.69ZM12.75 8.689c0-.864.933-1.406 1.683-.977l7.108 4.061a1.125 1.125 0 0 1 0 1.954l-7.108 4.061a1.125 1.125 0 0 1-1.683-.977V8.69Z" />
                    </svg>
                    Alle verarbeiten
                </button>
            @endif
        </div>
    </div>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>

    {{-- Stats Overview --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        {{-- Wartende Jobs --}}
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#d97706" style="width: 28px; height: 28px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>
                </div>
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Wartende Jobs</p>
                    <p style="font-size: 1.875rem; font-weight: 700; color: #111827; margin: 0;">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        {{-- Fehlgeschlagen --}}
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                @if($stats['failed'] > 0)
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#dc2626" style="width: 28px; height: 28px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                @else
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#16a34a" style="width: 28px; height: 28px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                @endif
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Fehlgeschlagen</p>
                    <p style="font-size: 1.875rem; font-weight: 700; color: {{ $stats['failed'] > 0 ? '#dc2626' : '#111827' }}; margin: 0;">{{ $stats['failed'] }}</p>
                </div>
            </div>
        </div>

        {{-- Queues --}}
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2563eb" style="width: 28px; height: 28px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
                    </svg>
                </div>
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Queues</p>
                    <p style="font-size: 1.875rem; font-weight: 700; color: #111827; margin: 0;">{{ count($stats['queues']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'pending'"
            wire:click="setActiveTab('pending')"
            icon="heroicon-o-clock"
            :badge="$stats['pending']"
        >
            Wartende Jobs
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'failed'"
            wire:click="setActiveTab('failed')"
            icon="heroicon-o-exclamation-triangle"
            :badge="$stats['failed']"
            :badge-color="$stats['failed'] > 0 ? 'danger' : 'gray'"
        >
            Fehlgeschlagen
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- Pending Jobs Table --}}
    @if($activeTab === 'pending')
        <x-filament::section class="mt-4">
            @if(count($pendingJobs) > 0)
                {{-- Bulk Actions Toolbar --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; border-radius: 8px 8px 0 0;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                            <input
                                type="checkbox"
                                wire:click="toggleSelectAllPending"
                                @checked(count($selectedPendingJobs) === count($pendingJobs) && count($pendingJobs) > 0)
                                style="width: 18px; height: 18px; border-radius: 4px; cursor: pointer;"
                            >
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">Alle auswählen</span>
                        </label>
                        @if(count($selectedPendingJobs) > 0)
                            <span style="font-size: 0.875rem; color: #6b7280;">{{ count($selectedPendingJobs) }} ausgewählt</span>
                        @endif
                    </div>
                    @if(count($selectedPendingJobs) > 0)
                        <button
                            wire:click="deleteSelectedPendingJobs"
                            wire:confirm="Wirklich {{ count($selectedPendingJobs) }} Jobs aus der Queue entfernen?"
                            style="display: inline-flex; align-items: center; gap: 6px; background: #dc2626; color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: background 0.15s;"
                            onmouseover="this.style.background='#b91c1c'"
                            onmouseout="this.style.background='#dc2626'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Ausgewählte löschen
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151; width: 50px;"></th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; width: 60px;">ID</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; min-width: 200px;">Job</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; width: 100px;">Queue</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151; width: 80px;">Versuche</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; width: 160px;">Erstellt</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; width: 160px;">Verfügbar ab</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151; width: 60px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingJobs as $job)
                                <tr style="border-bottom: 1px solid #e5e7eb; transition: background 0.15s; {{ in_array((string)$job['id'], $selectedPendingJobs) ? 'background: #eff6ff;' : '' }}" onmouseover="this.style.background='{{ in_array((string)$job['id'], $selectedPendingJobs) ? '#dbeafe' : '#f9fafb' }}'" onmouseout="this.style.background='{{ in_array((string)$job['id'], $selectedPendingJobs) ? '#eff6ff' : 'white' }}'">
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedPendingJobs"
                                            value="{{ $job['id'] }}"
                                            style="width: 18px; height: 18px; border-radius: 4px; cursor: pointer;"
                                        >
                                    </td>
                                    <td style="padding: 12px 16px; color: #6b7280; font-family: monospace; font-size: 0.75rem;">{{ $job['id'] }}</td>
                                    <td style="padding: 12px 16px;">
                                        <span style="font-weight: 500; color: #111827;">{{ $job['job_name'] }}</span>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <span style="display: inline-block; padding: 2px 10px; background: #dbeafe; color: #1d4ed8; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">{{ $job['queue'] }}</span>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center; color: #6b7280;">{{ $job['attempts'] }}</td>
                                    <td style="padding: 12px 16px; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">{{ $job['created_at'] }}</td>
                                    <td style="padding: 12px 16px; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">{{ $job['available_at'] }}</td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <button
                                            wire:click="deletePendingJob({{ $job['id'] }})"
                                            wire:confirm="Job wirklich aus der Queue entfernen?"
                                            style="background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; padding: 6px 8px; cursor: pointer; transition: background 0.15s;"
                                            onmouseover="this.style.background='#fecaca'"
                                            onmouseout="this.style.background='#fee2e2'"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#16a34a" style="width: 32px; height: 32px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Keine wartenden Jobs in der Queue</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Alle Aufgaben wurden abgearbeitet</p>
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- Failed Jobs Table --}}
    @if($activeTab === 'failed')
        <x-filament::section class="mt-4">
            @if(count($failedJobs) > 0)
                {{-- Bulk Actions Toolbar --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; border-radius: 8px 8px 0 0;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                            <input
                                type="checkbox"
                                wire:click="toggleSelectAllFailed"
                                @checked(count($selectedFailedJobs) === count($failedJobs) && count($failedJobs) > 0)
                                style="width: 18px; height: 18px; border-radius: 4px; cursor: pointer;"
                            >
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">Alle auswählen</span>
                        </label>
                        @if(count($selectedFailedJobs) > 0)
                            <span style="font-size: 0.875rem; color: #6b7280;">{{ count($selectedFailedJobs) }} ausgewählt</span>
                        @endif
                    </div>
                    @if(count($selectedFailedJobs) > 0)
                        <div style="display: flex; gap: 8px;">
                            <button
                                wire:click="retrySelectedFailedJobs"
                                wire:confirm="Wirklich {{ count($selectedFailedJobs) }} Jobs erneut versuchen?"
                                style="display: inline-flex; align-items: center; gap: 6px; background: #f59e0b; color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: background 0.15s;"
                                onmouseover="this.style.background='#d97706'"
                                onmouseout="this.style.background='#f59e0b'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Ausgewählte wiederholen
                            </button>
                            <button
                                wire:click="deleteSelectedFailedJobs"
                                wire:confirm="Wirklich {{ count($selectedFailedJobs) }} fehlgeschlagene Jobs löschen?"
                                style="display: inline-flex; align-items: center; gap: 6px; background: #dc2626; color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: background 0.15s;"
                                onmouseover="this.style.background='#b91c1c'"
                                onmouseout="this.style.background='#dc2626'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Ausgewählte löschen
                            </button>
                        </div>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151; width: 50px;"></th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; width: 100px;">UUID</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; min-width: 180px;">Job</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; width: 100px;">Queue</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; width: 160px;">Fehlgeschlagen</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151;">Fehler</th>
                                <th style="padding: 12px 16px; text-align: center; font-weight: 600; color: #374151; width: 100px;">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedJobs as $job)
                                <tr style="border-bottom: 1px solid #e5e7eb; transition: background 0.15s; {{ in_array($job['uuid'], $selectedFailedJobs) ? 'background: #fef2f2;' : '' }}" onmouseover="this.style.background='{{ in_array($job['uuid'], $selectedFailedJobs) ? '#fee2e2' : '#f9fafb' }}'" onmouseout="this.style.background='{{ in_array($job['uuid'], $selectedFailedJobs) ? '#fef2f2' : 'white' }}'">
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedFailedJobs"
                                            value="{{ $job['uuid'] }}"
                                            style="width: 18px; height: 18px; border-radius: 4px; cursor: pointer;"
                                        >
                                    </td>
                                    <td style="padding: 12px 16px; color: #6b7280; font-family: monospace; font-size: 0.75rem;">{{ Str::limit($job['uuid'], 8) }}</td>
                                    <td style="padding: 12px 16px;">
                                        <span style="font-weight: 500; color: #111827;">{{ $job['job_name'] }}</span>
                                    </td>
                                    <td style="padding: 12px 16px;">
                                        <span style="display: inline-block; padding: 2px 10px; background: #fee2e2; color: #dc2626; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">{{ $job['queue'] }}</span>
                                    </td>
                                    <td style="padding: 12px 16px; color: #6b7280; font-size: 0.75rem; white-space: nowrap;">{{ $job['failed_at'] }}</td>
                                    <td style="padding: 12px 16px;">
                                        <span style="color: #dc2626; font-size: 0.75rem;">{{ Str::limit($job['exception'], 80) }}</span>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <div style="display: flex; gap: 4px; justify-content: center;">
                                            <button
                                                wire:click="retryJob('{{ $job['uuid'] }}')"
                                                title="Erneut versuchen"
                                                style="background: #fef3c7; color: #d97706; border: none; border-radius: 6px; padding: 6px 8px; cursor: pointer; transition: background 0.15s;"
                                                onmouseover="this.style.background='#fde68a'"
                                                onmouseout="this.style.background='#fef3c7'"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                            <button
                                                wire:click="deleteFailedJob('{{ $job['uuid'] }}')"
                                                wire:confirm="Job wirklich löschen?"
                                                title="Löschen"
                                                style="background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; padding: 6px 8px; cursor: pointer; transition: background 0.15s;"
                                                onmouseover="this.style.background='#fecaca'"
                                                onmouseout="this.style.background='#fee2e2'"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#16a34a" style="width: 32px; height: 32px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Keine fehlgeschlagenen Jobs</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Alle Jobs wurden erfolgreich ausgeführt</p>
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- Queue Distribution --}}
    @if(count($stats['queues']) > 0)
        <x-filament::section class="mt-4">
            <x-slot name="heading">Queue-Verteilung</x-slot>
            <div class="flex flex-wrap gap-3">
                @foreach($stats['queues'] as $queue => $count)
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 8px; background: #f3f4f6;">
                        <span style="font-weight: 500; color: #374151;">{{ $queue }}</span>
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 1.5rem; height: 1.5rem; padding: 0 0.5rem; font-size: 0.75rem; font-weight: 700; color: white; background: #f59e0b; border-radius: 9999px;">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
