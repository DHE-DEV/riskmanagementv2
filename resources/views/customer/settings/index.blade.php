@extends('layouts.dashboard-minimal')

@section('title', 'Einstellungen - Passolution Travel Information Platform')

@php
    $active = 'customer-settings';
    $customer = auth('customer')->user();
    $featureService = app(\App\Services\CustomerFeatureService::class);
    $isImpersonating = session()->has('original_customer_id');
    $originalUser = session()->has('original_customer_id') ? \App\Models\Customer::find(session('original_customer_id')) : auth('customer')->user();
    $isSuperAdmin = in_array($originalUser->email, config('app.agentur_super_admin_emails', []));
    $settingsSection = request()->query('section', 'general');
    if ($isImpersonating && $settingsSection === 'general') {
        $settingsSection = 'master-data';
    }
    $loggedInEmployee = session('logged_in_employee_id') ? \App\Models\Employee::find(session('logged_in_employee_id')) : null;
    $isEmployeeLogin = $loggedInEmployee !== null;
@endphp

@push('styles')
<style>
    .main-content {
        display: flex !important;
        overflow: hidden !important;
        overflow-y: hidden !important;
    }
    .settings-sidebar {
        flex-shrink: 0;
        width: 304px;
        background: #f9fafb;
        overflow-y: auto;
        height: 100%;
        border-right: 1px solid #e5e7eb;
    }
    .settings-content {
        flex: 1;
        overflow-y: auto;
        height: 100%;
    }
    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        color: #374151;
        transition: all 0.15s;
        text-decoration: none;
    }
    .settings-nav-item:hover {
        background: #e5e7eb;
    }
    .settings-nav-item.active {
        background: white;
        border: 1px solid #e5e7eb;
        font-weight: 600;
        color: #111827;
    }
    .settings-nav-item i {
        width: 16px;
        text-align: center;
        color: #6b7280;
    }
    .settings-nav-item.active i {
        color: #2563eb;
    }
    .org-tree-node { position: relative; }
    .org-tree-node-row { display: flex; align-items: stretch; margin-bottom: 4px; }
    .org-tree-branch { position: relative; padding-left: 24px; }
    .org-tree-branch::before {
        content: ''; position: absolute; left: 11px; top: 0; bottom: 18px;
        border-left: 2px solid #d1d5db;
    }
    .org-tree-branch > .org-tree-node { position: relative; }
    .org-tree-branch > .org-tree-node::before {
        content: ''; position: absolute; left: -13px; top: 18px; width: 13px;
        border-top: 2px solid #d1d5db;
    }
    .org-tree-branch > .org-tree-node:last-child::after {
        content: ''; position: absolute; left: -13px; top: 18px; bottom: 0;
        background: white; width: 4px;
    }
    .org-tree-card {
        flex: 1; min-width: 0;
        border-radius: 8px; border: 1px solid #e5e7eb;
        background: #f9fafb; transition: all 0.15s;
    }
    .org-tree-card.checked { background: #eff6ff; border-color: #bfdbfe; }
    .org-tree-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .drag-item { transition: transform 0.15s, opacity 0.15s; }
    .drag-item.dragging { opacity: 0.4; }
    .drag-item.drag-over { border-color: #3b82f6 !important; box-shadow: 0 0 0 1px #3b82f6; }
    .drag-handle { cursor: grab; }
    .drag-handle:active { cursor: grabbing; }
    .settings-section-title {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #9ca3af;
        padding: 12px 12px 4px;
    }
</style>
@endpush

@section('content')
    @auth('customer')
        @php
            $settingsTourSteps = [
                ['target' => '.settings-sidebar', 'title' => 'Navigation', 'description' => 'Über die Seitenleiste navigieren Sie zwischen den verschiedenen Einstellungsbereichen. Die Bereiche sind thematisch gruppiert.'],
                ['target' => '#settings-nav-general', 'title' => 'Allgemein', 'description' => 'Unter <strong>Mein Profil</strong> verwalten Sie Ihre persönlichen Daten, Ihr Passwort, die Zwei-Faktor-Authentifizierung und die Produkteinführungen.'],
            ];
            if ($customer->branch_management_active) {
                $settingsTourSteps[] = ['target' => '#settings-nav-org', 'title' => 'Organisation', 'description' => '<strong>Stammdaten</strong> &ndash; Firmenadresse, Kontaktdaten und Abteilungen verwalten.<br><br><strong>Organisationsstruktur</strong> &ndash; Ihre Unternehmenshierarchie abbilden.<br><br><strong>Benutzerverwaltung</strong> &ndash; Mitarbeiter anlegen und Zugriffsrechte steuern.'];
            }
            $settingsTourSteps[] = ['target' => '#settings-nav-tip', 'title' => 'Travel Information Platform', 'description' => 'Hier konfigurieren Sie die einzelnen Module der Plattform: <strong>Travel Requirements Service</strong>, <strong>Global Travel Monitor</strong>, <strong>Travel Alert</strong>, <strong>Travel Data</strong>, <strong>Travel Link</strong>, <strong>Travel Information</strong> und <strong>Connected Services</strong>.'];
            $settingsTourSteps[] = ['target' => '.settings-content', 'title' => 'Einstellungsbereich', 'description' => 'Im Hauptbereich werden die Einstellungen des ausgewählten Menüpunkts angezeigt. Hier nehmen Sie Änderungen vor und speichern diese.', 'forceBelow' => true];
            $settingsTourSteps[] = ['target' => '#settings-nav-org', 'title' => 'Kostenlose Benutzerverwaltung', 'description' => 'Sie können <strong>kostenlos beliebig viele Benutzer</strong> in der Benutzerverwaltung anlegen. Jeder Benutzer erhält einen eigenen Zugang zur Passolution Travel Information Platform mit individuellen Berechtigungen.'];
        @endphp
        <x-page-tour
            tourKey="settings"
            tourLabel="Einstellungen"
            tourIcon="fas fa-cog"
            :steps='json_encode($settingsTourSteps)'
            finishCtaLabel="Möchten Sie jetzt weitere kostenlose Benutzer für ein maximales Benutzererlebnis anlegen?"
            :finishCtaUrl="route('customer.settings', ['section' => 'users'])"
        />
    @endauth

    {{-- Sidebar --}}
    <div class="settings-sidebar">
        <div class="p-4">
            <h2 class="text-sm font-bold text-gray-900 mb-3">
                <i class="fas fa-cog mr-2"></i>
                Einstellungen
            </h2>

            <nav class="space-y-1">
                @if(!$isImpersonating)
                <div id="settings-nav-general" class="settings-section-title">Allgemein</div>

                <a href="{{ route('customer.settings', ['section' => 'general']) }}"
                   class="settings-nav-item {{ $settingsSection === 'general' ? 'active' : '' }}">
                    <i class="fas fa-user"></i>
                    Mein Profil
                </a>
                @endif

                @if($customer->branch_management_active || $isSuperAdmin)
                <div id="settings-nav-org" class="settings-section-title mt-2">Organisation</div>

                <a href="{{ route('customer.settings', ['section' => 'master-data']) }}"
                   class="settings-nav-item {{ $settingsSection === 'master-data' ? 'active' : '' }}">
                    <i class="fas fa-database"></i>
                    Stammdaten
                </a>

                <a href="{{ route('customer.settings', ['section' => 'organization']) }}"
                   class="settings-nav-item {{ $settingsSection === 'organization' ? 'active' : '' }}">
                    <i class="fas fa-sitemap"></i>
                    Organisationsstruktur
                </a>

                <a href="{{ route('customer.settings', ['section' => 'users']) }}"
                   class="settings-nav-item {{ $settingsSection === 'users' ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    Benutzerverwaltung
                </a>
                @endif

                <div id="settings-nav-tip" class="settings-section-title mt-2">Travel Information Platform</div>

                <a href="{{ route('customer.settings', ['section' => 'travel-requirements']) }}"
                   class="settings-nav-item {{ $settingsSection === 'travel-requirements' ? 'active' : '' }}">
                    <i class="fas fa-passport"></i>
                    Travel Requirements Service
                </a>

                <a href="{{ route('customer.settings', ['section' => 'global-travel-monitor']) }}"
                   class="settings-nav-item {{ $settingsSection === 'global-travel-monitor' ? 'active' : '' }}">
                    <i class="fas fa-globe"></i>
                    Global Travel Monitor
                </a>

                <a href="{{ route('customer.settings', ['section' => 'travel-alert']) }}"
                   class="settings-nav-item {{ $settingsSection === 'travel-alert' ? 'active' : '' }}">
                    <i class="fas fa-triangle-exclamation"></i>
                    Travel Alert
                </a>

                <a href="{{ route('customer.settings', ['section' => 'travel-data']) }}"
                   class="settings-nav-item {{ $settingsSection === 'travel-data' ? 'active' : '' }}">
                    <i class="fas fa-route"></i>
                    Travel Data
                </a>

                <a href="{{ route('customer.settings', ['section' => 'travel-link']) }}"
                   class="settings-nav-item {{ $settingsSection === 'travel-link' ? 'active' : '' }}">
                    <i class="fas fa-link"></i>
                    Travel Link
                </a>

                <a href="{{ route('customer.settings', ['section' => 'travel-information']) }}"
                   class="settings-nav-item {{ $settingsSection === 'travel-information' ? 'active' : '' }}">
                    <i class="fas fa-book-atlas"></i>
                    Travel Information
                </a>

                <a href="{{ route('customer.settings', ['section' => 'connected-services']) }}"
                   class="settings-nav-item {{ $settingsSection === 'connected-services' ? 'active' : '' }}">
                    <i class="fas fa-plug"></i>
                    Connected Services
                </a>
            </nav>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="settings-content">
        <div class="p-6" x-data="settingsManager()">
            @if($settingsSection === 'general' && !$isImpersonating)
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Mein Profil</h3>
                <p class="text-sm text-gray-500 mb-6">Verwalten und bearbeiten Sie Ihre persönlichen Daten und Firmeninformationen.</p>

                {{-- Profilbild --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">Profilbild</h4>
                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-semibold overflow-hidden border-2 border-gray-200"
                                 id="avatar-preview">
                                @if($customer->avatar)
                                    <img src="{{ Storage::disk('public')->url($customer->avatar) }}" alt="Profilbild" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($isEmployeeLogin ? $loggedInEmployee->first_name : $customer->name, 0, 1)) }}
                                @endif
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <label class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors cursor-pointer inline-flex items-center gap-1">
                                    <i class="fas fa-upload"></i> Foto hochladen
                                    <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="uploadAvatar($event)">
                                </label>
                                @if($customer->avatar)
                                <button @click="deleteAvatar()" class="px-4 py-2 text-xs text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors inline-flex items-center gap-1">
                                    <i class="fas fa-trash"></i> Entfernen
                                </button>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400">JPG, PNG oder WebP. Maximal 2 MB.</p>
                        </div>
                    </div>
                </div>

                {{-- Persönliche Daten --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-900">Persönliche Daten</h4>
                        <button @click="editSection = editSection === 'personal' ? null : 'personal'"
                                class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <i class="fas fa-pen text-[10px]"></i>
                            <span x-text="editSection === 'personal' ? 'Abbrechen' : 'Bearbeiten'"></span>
                        </button>
                    </div>

                    {{-- View Mode --}}
                    <div x-show="editSection !== 'personal'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-gray-500">Name</span>
                            <p class="font-medium text-gray-900" x-text="personal.name"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">E-Mail</span>
                            <p class="font-medium text-gray-900" x-text="personal.email"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Telefon</span>
                            <p class="font-medium text-gray-900" x-text="personal.phone || '—'"></p>
                        </div>
                        @if($isEmployeeLogin)
                        <div>
                            <span class="text-xs text-gray-500">Firma</span>
                            <p class="font-medium text-gray-900">{{ $customer->company_name }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Position</span>
                            <p class="font-medium text-gray-900">{{ $loggedInEmployee->position ?: '—' }}</p>
                        </div>
                        @else
                        <div>
                            <span class="text-xs text-gray-500">Kundentyp</span>
                            <p class="font-medium text-gray-900">
                                @if($customer->customer_type === 'private') Privatkunde
                                @else Firmenkunde @endif
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Registriert am</span>
                            <p class="font-medium text-gray-900">{{ $customer->created_at?->format('d.m.Y') }}</p>
                        </div>
                        @endif
                        {{-- <div>
                            <span class="text-xs text-gray-500">Login via</span>
                            <p class="font-medium text-gray-900">{{ $customer->provider ?: 'E-Mail' }}</p>
                        </div> --}}
                    </div>

                    {{-- Edit Mode --}}
                    <form x-show="editSection === 'personal'" x-cloak @submit.prevent="savePersonal" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                            <input type="text" x-model="personal.name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">E-Mail</label>
                            <input type="email" x-model="personal.email" readonly disabled class="w-full px-3 py-2 border border-gray-200 bg-gray-100 text-gray-500 rounded-lg text-sm cursor-not-allowed">
                            <p class="text-[11px] text-gray-400 mt-1">Die E-Mail-Adresse kann nicht geändert werden. Bitte wenden Sie sich an den Support.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Telefon</label>
                            <input type="text" x-model="personal.phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-2 pt-2">
                            <button type="button" @click="editSection = null" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                            <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                <i class="fas fa-save mr-1"></i> Speichern
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Gruppenzuordnung --}}
                @php
                    $ownerEmployee = \App\Models\Employee::where('customer_id', $customer->id)
                        ->where('email', $customer->email)
                        ->with('groups:id,name')
                        ->first();
                    $employeeGroups = $ownerEmployee ? $ownerEmployee->groups : collect();
                @endphp
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">Gruppenzuordnung</h4>
                    @if($employeeGroups->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($employeeGroups as $group)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-purple-50 text-purple-800 border border-purple-200">
                                    <i class="fas fa-layer-group mr-1.5 text-[10px] text-purple-500"></i>{{ $group->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Keiner Gruppe zugeordnet.</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-3">
                        Die Gruppenzuordnung kann unter
                        <a href="{{ route('customer.settings', ['section' => 'users']) }}" class="text-blue-600 hover:underline">Benutzerverwaltung</a>
                        geändert werden.
                    </p>
                </div>

                {{-- Passwort ändern (nur für User mit lokalem Passwort) --}}
                @if(!$customer->provider)
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">Passwort ändern</h4>
                    <form @submit.prevent="changePassword">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Aktuelles Passwort <span class="text-red-500">*</span></label>
                                <input type="password" x-model="passwords.current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Neues Passwort <span class="text-red-500">*</span></label>
                                <input type="password" x-model="passwords.password" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Passwort bestätigen <span class="text-red-500">*</span></label>
                                <input type="password" x-model="passwords.password_confirmation" required minlength="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                <i class="fas fa-key mr-1"></i> Passwort ändern
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Zwei-Faktor-Authentifizierung --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="twoFactorManager()">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Zwei-Faktor-Authentifizierung</h4>
                            <p class="text-xs text-gray-500 mt-1">Schützen Sie Ihr Konto mit einer zusätzlichen Sicherheitsebene.</p>
                        </div>
                        @if($customer->two_factor_confirmed_at)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> Aktiviert
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                <i class="fas fa-minus-circle mr-1"></i> Deaktiviert
                            </span>
                        @endif
                    </div>

                    @if($customer->two_factor_confirmed_at)
                        {{-- 2FA ist aktiv - Optionen zum Deaktivieren und Recovery Codes --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 text-sm text-green-700 bg-green-50 px-3 py-2.5 rounded-lg">
                                <i class="fas fa-shield-halved"></i>
                                <span>Ihr Konto ist durch Zwei-Faktor-Authentifizierung geschützt.</span>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button @click="showRecoveryCodes()" class="px-3 py-2 text-xs bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors flex items-center gap-1">
                                    <i class="fas fa-key"></i> Wiederherstellungscodes anzeigen
                                </button>
                                <button @click="regenerateRecoveryCodes()" class="px-3 py-2 text-xs bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors flex items-center gap-1">
                                    <i class="fas fa-rotate"></i> Codes neu generieren
                                </button>
                                <button @click="confirmDisable = true" class="px-3 py-2 text-xs bg-red-50 border border-red-200 text-red-700 hover:bg-red-100 rounded-lg transition-colors flex items-center gap-1">
                                    <i class="fas fa-power-off"></i> 2FA deaktivieren
                                </button>
                            </div>

                            {{-- Recovery Codes anzeigen --}}
                            <div x-show="recoveryCodes.length > 0" x-cloak class="mt-3 bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <h5 class="text-xs font-semibold text-gray-900 mb-2 flex items-center gap-1">
                                    <i class="fas fa-key text-gray-400"></i> Wiederherstellungscodes
                                </h5>
                                <p class="text-xs text-gray-500 mb-3">Bewahren Sie diese Codes sicher auf. Jeder Code kann nur einmal verwendet werden.</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <template x-for="code in recoveryCodes" :key="code">
                                        <code class="bg-white border border-gray-200 rounded px-3 py-1.5 text-xs font-mono text-gray-800" x-text="code"></code>
                                    </template>
                                </div>
                                <button @click="recoveryCodes = []" class="mt-3 text-xs text-gray-500 hover:text-gray-700">Ausblenden</button>
                            </div>

                            {{-- Deaktivieren-Bestätigung --}}
                            <div x-show="confirmDisable" x-cloak class="mt-3 bg-red-50 rounded-lg p-4 border border-red-200">
                                <p class="text-xs text-red-700 mb-3">Geben Sie Ihr Passwort ein, um die Zwei-Faktor-Authentifizierung zu deaktivieren.</p>
                                <form @submit.prevent="disable2FA">
                                    <div class="flex gap-2">
                                        <input type="password" x-model="password" required placeholder="Passwort" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                        <button type="submit" class="px-3 py-2 text-xs text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Deaktivieren</button>
                                        <button type="button" @click="confirmDisable = false; password = ''" class="px-3 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                    </div>
                                    <p x-show="error" x-cloak class="text-xs text-red-600 mt-2" x-text="error"></p>
                                </form>
                            </div>
                        </div>
                    @else
                        {{-- 2FA ist nicht aktiv - Einrichten --}}
                        <div x-show="!setupStarted">
                            <p class="text-xs text-gray-600 mb-3">
                                Mit Zwei-Faktor-Authentifizierung wird bei jeder Anmeldung ein zusätzlicher Code aus Ihrer Authenticator-App abgefragt.
                            </p>
                            <button @click="enable2FA()" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                                <i class="fas fa-shield-halved"></i> 2FA jetzt einrichten
                            </button>
                            <p x-show="error" x-cloak class="text-xs text-red-600 mt-2" x-text="error"></p>
                        </div>

                        {{-- Setup-Flow: QR-Code + Bestätigung --}}
                        <div x-show="setupStarted" x-cloak>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <h5 class="text-xs font-semibold text-blue-900 mb-2">Schritt 1: Authenticator-App scannen</h5>
                                <p class="text-xs text-blue-700 mb-3">Scannen Sie den QR-Code mit einer Authenticator-App (z.B. Google Authenticator, Authy oder Microsoft Authenticator).</p>
                                <div class="flex flex-col items-center gap-3">
                                    <div x-show="qrCodeSvg" x-html="qrCodeSvg" class="bg-white p-3 rounded-lg border border-blue-200 inline-block"></div>
                                    <div x-show="secretKey">
                                        <p class="text-xs text-blue-700 mb-1">Oder geben Sie diesen Schlüssel manuell ein:</p>
                                        <code class="bg-white border border-blue-200 rounded px-3 py-1.5 text-xs font-mono text-blue-900 select-all" x-text="secretKey"></code>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <h5 class="text-xs font-semibold text-gray-900 mb-2">Schritt 2: Code bestätigen</h5>
                                <p class="text-xs text-gray-500 mb-3">Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein, um die Einrichtung abzuschließen.</p>
                                <form @submit.prevent="confirm2FA">
                                    <div class="flex gap-2">
                                        <input type="text" x-model="confirmCode" required placeholder="6-stelliger Code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono tracking-widest text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">Bestätigen</button>
                                        <button type="button" @click="cancel2FA()" class="px-3 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                    </div>
                                    <p x-show="error" x-cloak class="text-xs text-red-600 mt-2" x-text="error"></p>
                                </form>
                            </div>

                            {{-- Recovery Codes nach Bestätigung --}}
                            <div x-show="recoveryCodes.length > 0" x-cloak class="mt-4 bg-green-50 rounded-lg p-4 border border-green-200">
                                <h5 class="text-xs font-semibold text-green-900 mb-2 flex items-center gap-1">
                                    <i class="fas fa-check-circle"></i> 2FA erfolgreich eingerichtet!
                                </h5>
                                <p class="text-xs text-green-700 mb-3">Speichern Sie diese Wiederherstellungscodes an einem sicheren Ort. Sie benötigen diese, falls Sie keinen Zugriff auf Ihre Authenticator-App haben.</p>
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <template x-for="code in recoveryCodes" :key="code">
                                        <code class="bg-white border border-green-200 rounded px-3 py-1.5 text-xs font-mono text-gray-800" x-text="code"></code>
                                    </template>
                                </div>
                                <button @click="location.reload()" class="px-4 py-2 text-xs text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                                    <i class="fas fa-check mr-1"></i> Fertig
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Produkteinführungen --}}
                @if(config('app.customer_product_tours_enabled', true))
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="{ toursOpen: false }">
                    <button @click="toursOpen = !toursOpen" class="flex items-center justify-between w-full text-left">
                        <h4 class="text-sm font-semibold text-gray-900">Produkteinführungen</h4>
                        <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" :class="toursOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="toursOpen" x-collapse x-cloak class="mt-4">

                    {{-- Oberfläche --}}
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Oberfläche</p>
                            <p class="text-xs text-gray-500">
                                @if($customer->has_seen_platform_tour)
                                    Abgeschlossen
                                @else
                                    Wird beim nächsten Besuch angezeigt
                                @endif
                            </p>
                        </div>
                        @if($customer->has_seen_platform_tour)
                        <button onclick="resetTour('platform')" class="px-3 py-1.5 text-xs text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors inline-flex items-center gap-1">
                            <i class="fas fa-rotate-right"></i> Erneut starten
                        </button>
                        @endif
                    </div>

                    {{-- Global Travel Monitor --}}
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Global Travel Monitor</p>
                            <p class="text-xs text-gray-500">
                                @if($customer->has_seen_gtm_tour)
                                    Abgeschlossen
                                @else
                                    Wird beim nächsten Besuch angezeigt
                                @endif
                            </p>
                        </div>
                        @if($customer->has_seen_gtm_tour)
                        <button onclick="resetTour('gtm')" class="px-3 py-1.5 text-xs text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors inline-flex items-center gap-1">
                            <i class="fas fa-rotate-right"></i> Erneut starten
                        </button>
                        @endif
                    </div>

                    @php
                        $tourItems = [
                            ['key' => 'travel_alert', 'label' => 'Travel Alert'],
                            ['key' => 'trs', 'label' => 'Travel Requirements Service'],
                            ['key' => 'entry_conditions', 'label' => 'Einreisebestimmungen'],
                            ['key' => 'travel_data', 'label' => 'Travel Data'],
                            ['key' => 'travel_links', 'label' => 'Travel Links'],
                            ['key' => 'booking', 'label' => 'Buchungsmöglichkeit'],
                            ['key' => 'airports', 'label' => 'Flughäfen'],
                            ['key' => 'branches', 'label' => 'Filialen & Standorte'],
                            ['key' => 'my_travelers', 'label' => 'Meine Reisenden'],
                            ['key' => 'customer_events', 'label' => 'Meine Ereignisse'],
                            ['key' => 'cruise', 'label' => 'Kreuzfahrt'],
                            ['key' => 'business_visa', 'label' => 'Business Visum'],
                            ['key' => 'visumpoint', 'label' => 'Visum Check'],
                            ['key' => 'settings', 'label' => 'Einstellungen'],
                        ];
                    @endphp

                    @foreach($tourItems as $i => $tour)
                        @php $field = 'has_seen_' . $tour['key'] . '_tour'; @endphp
                        <div class="flex items-center justify-between py-3 {{ $i < count($tourItems) - 1 ? 'border-b border-gray-100' : '' }}">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $tour['label'] }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $customer->$field ? 'Abgeschlossen' : 'Wird beim nächsten Besuch angezeigt' }}
                                </p>
                            </div>
                            @if($customer->$field)
                            <button onclick="resetTour('{{ $tour['key'] }}')" class="px-3 py-1.5 text-xs text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors inline-flex items-center gap-1">
                                <i class="fas fa-rotate-right"></i> Erneut starten
                            </button>
                            @endif
                        </div>
                    @endforeach
                    </div>
                </div>
                @endif

                <script>
                function resetTour(tour) {
                    fetch('/tour/reset', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ tour: tour })
                    }).then(r => r.json()).then(data => {
                        if (data.success) location.reload();
                    });
                }
                </script>

            @elseif($settingsSection === 'notifications')
                <div x-data="{ notifTab: 'travelalert' }">

                {{-- Tab-Leiste --}}
                <div class="tab-navigation flex border-b border-gray-200 bg-white -mx-6 -mt-6 px-4 mb-6">
                    <button @click="notifTab = 'travelalert'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="notifTab === 'travelalert' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-shield-exclamation mr-2"></i>
                        TravelAlert
                    </button>
                    <button @click="notifTab = 'reisen'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="notifTab === 'reisen' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-suitcase-rolling mr-2"></i>
                        Meine Reisen
                    </button>
                </div>

                {{-- Tab: TravelAlert --}}
                <div x-show="notifTab === 'travelalert'">
                    @if(auth('customer')->user()->isFeatureEnabled('navigation_risk_overview_enabled'))
                    @php
                        $notifCustomer = auth('customer')->user();
                        $notifTemplateCount = \App\Models\NotificationTemplate::forCustomer($notifCustomer->id)->count();
                        $notifCustomTemplateCount = $notifCustomer->notificationTemplates()->count();
                        $notifSystemTemplateCount = \App\Models\NotificationTemplate::system()->count();
                    @endphp

                    {{-- Globale Einstellungen --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Globale Einstellungen</h4>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-700">Automatische Benachrichtigungen</p>
                                <p class="text-[10px] text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Wenn aktiviert, werden E-Mails basierend auf Ihren Regeln versendet.</p>
                            </div>
                            <form method="POST" action="{{ route('customer.notification-settings.toggle') }}">
                                @csrf
                                <button type="submit" class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-11 h-6 {{ $notifCustomer->notifications_enabled ? 'bg-blue-600' : 'bg-gray-200' }} rounded-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all {{ $notifCustomer->notifications_enabled ? 'after:translate-x-full after:border-white' : '' }}"></div>
                                </button>
                            </form>
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $notifCustomer->notifications_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                <i class="fas {{ $notifCustomer->notifications_enabled ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                {{ $notifCustomer->notifications_enabled ? 'Aktiviert' : 'Deaktiviert' }}
                            </span>
                        </div>
                    </div>

                    {{-- E-Mail-Vorlagen --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="{
                        showTemplateModal: false,
                        editTemplateId: null,
                        templates: [],
                        loading: true,
                        async init() { await this.loadTemplates(); },
                        async loadTemplates() {
                            this.loading = true;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.templates.index') }}?source=travel-alert', { headers: { 'Accept': 'application/json' } });
                                if (r.ok) {
                                    const d = await r.json();
                                    this.templates = d.templates || d;
                                }
                            } catch(e) {}
                            this.loading = false;
                        },
                        openCreate() {
                            this.editTemplateId = null;
                            this.showTemplateModal = true;
                            Livewire.dispatch('load-template', { id: null });
                        },
                        openEdit(id) {
                            this.editTemplateId = id;
                            this.showTemplateModal = true;
                            Livewire.dispatch('load-template', { id: id });
                        },
                        async sendTestMail(id) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Test-Mail wird versendet...', type: 'info' } }));
                            try {
                                const r = await fetch('/customer/notification-settings/templates/' + id + '/test', {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                const d = await r.json();
                                if (d.success) {
                                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message, type: 'success' } }));
                                    window.dispatchEvent(new CustomEvent('reload-logs'));
                                } else {
                                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message || 'Fehler beim Versenden.', type: 'error' } }));
                                }
                            } catch(e) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Fehler beim Versenden.', type: 'error' } })); }
                        },
                        async deleteTemplate(id) {
                            if (!confirm('Möchten Sie diese Vorlage wirklich löschen?')) return;
                            try {
                                const r = await fetch('/customer/notification-settings/templates/' + id, {
                                    method: 'DELETE',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                if (r.ok) this.loadTemplates();
                            } catch(e) {}
                        },
                    }"
                    x-on:template-saved.window="showTemplateModal = false; loadTemplates()"
                    x-on:template-deleted.window="showTemplateModal = false; loadTemplates()">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-file-alt mr-2 text-blue-500"></i>E-Mail-Vorlagen</h4>
                            <button @click="openCreate()" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Neue E-Mail-Vorlage
                            </button>
                        </div>

                        <div x-show="loading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!loading && templates.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-file-alt text-2xl mb-2"></i>
                            <p class="text-xs">Keine Vorlagen vorhanden.</p>
                        </div>

                        <div x-show="!loading && templates.length > 0" class="space-y-2">
                            <template x-for="tpl in templates" :key="tpl.id">
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-medium text-gray-900" x-text="tpl.name"></span>
                                                <span x-show="tpl.is_system" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800">
                                                    <i class="fas fa-lock mr-0.5 text-[8px]"></i> System
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-gray-500"><i class="fas fa-envelope mr-1"></i>Betreff: <span x-text="tpl.subject"></span></p>
                                        </div>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-ellipsis-vertical text-sm"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition x-cloak
                                                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                                <button x-show="!tpl.is_system" @click="openEdit(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                                </button>
                                                <button @click="sendTestMail(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-paper-plane w-4 text-center text-amber-500"></i> Test-Mail versenden
                                                </button>
                                                <div x-show="!tpl.is_system" class="border-t border-gray-100 my-1"></div>
                                                <button x-show="!tpl.is_system" @click="deleteTemplate(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fas fa-trash w-4 text-center"></i> Löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Template Modal --}}
                        <div x-show="showTemplateModal" x-cloak class="fixed z-[10000] flex items-center justify-center" style="top: 64px; bottom: 56px; left: 0; right: 0; padding: 8px;" @keydown.escape.window="showTemplateModal = false">
                            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showTemplateModal = false"></div>
                            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 flex flex-col" style="max-height: 100%;">
                                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-gray-900" x-text="editTemplateId ? 'E-Mail-Vorlage bearbeiten' : 'Neue E-Mail-Vorlage'"></h4>
                                    <button @click="showTemplateModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-lg"></i></button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-6">
                                    @livewire('customer.notification-template-form', ['source' => 'travel-alert'], key('settings-tpl-form'))
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Regeln --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="{
                        rules: [], rulesLoading: true,
                        async init() { await this.loadRules(); },
                        async loadRules() {
                            this.rulesLoading = true;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.rules.json') }}?source=travel-alert', { headers: { 'Accept': 'application/json' } });
                                if (r.ok) { const d = await r.json(); this.rules = d.rules || []; }
                            } catch(e) {}
                            this.rulesLoading = false;
                        },
                        async sendRuleTestMail(id) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Test-Mail wird versendet...', type: 'info' } }));
                            try {
                                const r = await fetch('/customer/notification-settings/rules/' + id + '/test', {
                                    method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                const d = await r.json();
                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message, type: d.success ? 'success' : 'error' } }));
                                if (d.success) window.dispatchEvent(new CustomEvent('reload-logs'));
                            } catch(e) {
                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Fehler beim Versenden.', type: 'error' } }));
                            }
                        },
                        async deleteRule(id) {
                            if (!confirm('Möchten Sie diese Regel wirklich löschen?')) return;
                            try {
                                const r = await fetch('/customer/notification-settings/rules/' + id, {
                                    method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                if (r.ok) this.loadRules();
                            } catch(e) {}
                        },
                        showRuleModal: false,
                        editRuleId: null,
                        openCreateRule() { this.editRuleId = null; this.showRuleModal = true; Livewire.dispatch('load-rule', { id: null }); },
                        openEditRule(id) { this.editRuleId = id; this.showRuleModal = true; Livewire.dispatch('load-rule', { id: id }); },
                    }" x-on:reload-rules.window="loadRules()" x-on:rule-saved.window="showRuleModal = false; loadRules()" x-on:rule-deleted.window="showRuleModal = false; loadRules()">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-list-check mr-2 text-blue-500"></i>Benachrichtigungs-Regeln</h4>
                            <button @click="openCreateRule()" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Neue Regel
                            </button>
                        </div>

                        <div x-show="rulesLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!rulesLoading && rules.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p class="text-xs">Noch keine Regeln erstellt.</p>
                        </div>

                        <div x-show="!rulesLoading && rules.length > 0" class="space-y-2">
                            <template x-for="rule in rules" :key="rule.id">
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-medium text-gray-900" x-text="rule.name"></span>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                                      :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                                      x-text="rule.is_active ? 'aktiv' : 'inaktiv'"></span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-gray-500">
                                                <span x-show="rule.risk_level_labels.length"><i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i><span x-text="rule.risk_level_labels.join(', ')"></span></span>
                                                <span x-show="rule.category_labels.length"><i class="fas fa-tag text-blue-500 mr-1"></i><span x-text="rule.category_labels.join(', ')"></span></span>
                                                <span><i class="fas fa-globe text-green-500 mr-1"></i><span x-text="rule.country_count ? rule.country_count + ' Länder' : 'Alle Länder'"></span></span>
                                                <span><i class="fas fa-envelope text-purple-500 mr-1"></i><span x-text="rule.recipients_count"></span> Empfänger</span>
                                            </div>
                                        </div>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-ellipsis-vertical text-sm"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition x-cloak
                                                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                                <button @click="openEditRule(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                                </button>
                                                <button @click="sendRuleTestMail(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-paper-plane w-4 text-center text-amber-500"></i> Test-Mail versenden
                                                </button>
                                                <div class="border-t border-gray-100 my-1"></div>
                                                <button @click="deleteRule(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fas fa-trash w-4 text-center"></i> Löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Rule Modal --}}
                        <div x-show="showRuleModal" x-cloak class="fixed z-[10000] flex items-center justify-center" style="top: 64px; bottom: 56px; left: 0; right: 0; padding: 8px;" @keydown.escape.window="if(showRuleModal) showRuleModal = false">
                            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showRuleModal = false"></div>
                            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 flex flex-col" style="max-height: 100%;">
                                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-gray-900" x-text="editRuleId ? 'Regel bearbeiten' : 'Neue Benachrichtigungs-Regel'"></h4>
                                    <button @click="showRuleModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-lg"></i></button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-6">
                                    @livewire('customer.notification-rule-form', ['source' => 'travel-alert'], key('settings-rule-form'))
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Versandprotokoll --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5" x-on:reload-logs.window="loadLogs(1)" x-data="{
                        logs: [], logsMeta: {}, logsLoading: true, logsPage: 1,
                        async init() { await this.loadLogs(); },
                        async loadLogs(page) {
                            this.logsLoading = true;
                            if (page) this.logsPage = page;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.logs') }}?source=travel-alert&page=' + this.logsPage, { headers: { 'Accept': 'application/json' } });
                                if (r.ok) {
                                    const d = await r.json();
                                    this.logs = d.data || [];
                                    this.logsMeta = { current_page: d.current_page, last_page: d.last_page, total: d.total, from: d.from, to: d.to };
                                }
                            } catch(e) {}
                            this.logsLoading = false;
                        }
                    }">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-history mr-2 text-blue-500"></i>Versandprotokoll</h4>
                            <span class="text-[10px] text-gray-400" x-show="logsMeta.total" x-text="logsMeta.total + ' Einträge'"></span>
                        </div>

                        <div x-show="logsLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!logsLoading && logs.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p class="text-xs">Noch keine Nachrichten versendet.</p>
                        </div>

                        <div x-show="!logsLoading && logs.length > 0">
                            <div class="space-y-2 mb-4">
                                <template x-for="log in logs" :key="log.id">
                                    <div class="border rounded-lg p-3 text-xs" :class="log.status === 'sent' ? 'border-gray-200' : 'border-red-200 bg-red-50'">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <i class="fas text-[10px]" :class="log.status === 'sent' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'"></i>
                                                    <span class="font-medium text-gray-900 truncate" x-text="log.subject"></span>
                                                    <span x-show="log.is_test" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-800">Test</span>
                                                </div>
                                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-gray-500">
                                                    <span><i class="fas fa-envelope mr-1"></i><span x-text="log.recipient_email"></span></span>
                                                    <span x-show="log.template_name"><i class="fas fa-file-alt mr-1"></i>Vorlage: <span x-text="log.template_name"></span></span>
                                                    <span x-show="log.rule_name"><i class="fas fa-list-check mr-1"></i>Regel: <span x-text="log.rule_name"></span></span>
                                                    <span x-show="log.notification_rule && !log.rule_name"><i class="fas fa-list-check mr-1"></i>Regel: <span x-text="log.notification_rule?.name"></span></span>
                                                </div>
                                                <p x-show="log.error_message" class="text-[10px] text-red-600 mt-1" x-text="log.error_message"></p>
                                            </div>
                                            <div class="text-[10px] text-gray-400 flex-shrink-0 text-right">
                                                <div x-text="new Date(log.created_at).toLocaleDateString('de-DE')"></div>
                                                <div x-text="new Date(log.created_at).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'})"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Paginator --}}
                            <div x-show="logsMeta.last_page > 1" class="flex items-center justify-between">
                                <p class="text-[10px] text-gray-500" x-text="'Seite ' + logsMeta.current_page + ' von ' + logsMeta.last_page"></p>
                                <div class="flex gap-1">
                                    <button @click="loadLogs(logsPage - 1)" :disabled="logsPage <= 1"
                                        :class="logsPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-2 py-1 text-[10px] rounded border border-gray-200">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <template x-for="p in logsMeta.last_page" :key="p">
                                        <button @click="loadLogs(p)"
                                            class="px-2 py-1 text-[10px] rounded border"
                                            :class="p === logsMeta.current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                                            x-text="p" x-show="Math.abs(p - logsMeta.current_page) < 3 || p === 1 || p === logsMeta.last_page">
                                        </button>
                                    </template>
                                    <button @click="loadLogs(logsPage + 1)" :disabled="logsPage >= logsMeta.last_page"
                                        :class="logsPage >= logsMeta.last_page ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-2 py-1 text-[10px] rounded border border-gray-200">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @else
                    <div class="bg-white rounded-lg border border-gray-200 p-5 text-center">
                        <i class="fas fa-shield-exclamation text-3xl text-gray-300 mb-2"></i>
                        <p class="text-xs text-gray-500">TravelAlert ist nicht aktiviert.</p>
                    </div>
                    @endif
                </div>

                {{-- Globale Toast Notification --}}
                <div x-data="{ msg: '', type: 'success', visible: false }"
                     x-on:show-toast.window="msg = $event.detail.message; type = $event.detail.type; visible = true; if(type !== 'info') setTimeout(() => visible = false, 5000)">
                    <div x-show="visible" x-cloak x-transition
                         class="fixed top-20 right-6 max-w-sm z-[10001] rounded-lg shadow-lg border px-4 py-3 flex items-start gap-3"
                         :class="{ 'bg-green-50 border-green-200': type==='success', 'bg-red-50 border-red-200': type==='error', 'bg-blue-50 border-blue-200': type==='info' }">
                        <i class="fas mt-0.5" :class="{ 'fa-check-circle text-green-500': type==='success', 'fa-exclamation-circle text-red-500': type==='error', 'fa-spinner fa-spin text-blue-500': type==='info' }"></i>
                        <p class="flex-1 text-xs font-medium" :class="{ 'text-green-800': type==='success', 'text-red-800': type==='error', 'text-blue-800': type==='info' }" x-text="msg"></p>
                        <button @click="visible = false" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                {{-- Tab: Meine Reisen --}}
                <div x-show="notifTab === 'reisen'" x-cloak>
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        @livewire('customer.travelers-auto-refresh-settings')
                    </div>
                </div>

                </div>

            @elseif($settingsSection === 'master-data')
                <div x-data="masterDataManager()">

                {{-- Tab-Leiste im travel-alert Stil (volle Breite, außerhalb p-6) --}}
                <div class="tab-navigation flex border-b border-gray-200 bg-white -mx-6 -mt-6 px-4 mb-6">
                    <button @click="mdTab = 'uebersicht'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="mdTab === 'uebersicht' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-building mr-2"></i>
                        Übersicht
                    </button>
                    <button @click="mdTab = 'adressen'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="mdTab === 'adressen' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        Adressen
                    </button>
                    <button @click="mdTab = 'rufnummern'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="mdTab === 'rufnummern' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-phone mr-2"></i>
                        Rufnummern
                    </button>
                    <button @click="mdTab = 'emails'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="mdTab === 'emails' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-envelope mr-2"></i>
                        E-Mail
                    </button>
                    <button @click="mdTab = 'web'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="mdTab === 'web' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-globe mr-2"></i>
                        Web
                    </button>
                    <button @click="mdTab = 'abteilungen'; if (!deptsLoaded) loadDepartments();"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="mdTab === 'abteilungen' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-folder-tree mr-2"></i>
                        Abteilungen
                    </button>
                </div>

                {{-- ==================== Tab: Übersicht ==================== --}}
                <div x-show="mdTab === 'uebersicht'">
                    @php $branchCount = \App\Models\Branch::where('customer_id', $customer->id)->count(); @endphp
                    @php $employeeCount = \App\Models\Employee::where('customer_id', $customer->id)->count(); @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $branchCount }}</p>
                            <p class="text-xs text-gray-500 mt-1">Adressen</p>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $employeeCount }}</p>
                            <p class="text-xs text-gray-500 mt-1">Benutzer</p>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $customer->company_city ?: '—' }}</p>
                            <p class="text-xs text-gray-500 mt-1">Hauptsitz</p>
                        </div>
                        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center cursor-pointer hover:bg-gray-50 transition-colors"
                             x-data="{ copied: false }"
                             @click="
                                const tmp = document.createElement('textarea');
                                tmp.value = '{{ $customer->app_code }}';
                                document.body.appendChild(tmp);
                                tmp.select();
                                document.execCommand('copy');
                                document.body.removeChild(tmp);
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                             "
                             title="In Zwischenablage kopieren">
                            <p class="text-2xl font-bold text-gray-900 font-mono tracking-wider">{{ $customer->app_code }}</p>
                            <p class="text-xs mt-1" :class="copied ? 'text-green-600' : 'text-gray-500'" x-text="copied ? 'Kopiert!' : 'App-Code'"></p>
                        </div>
                    </div>

                    {{-- Kundentyp --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="customerTypeManager()">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Kundentyp</h4>
                        {{-- <p class="text-xs text-gray-500 mb-4">Bitte wählen Sie aus, ob Sie Firmenkunde oder Privatkunde sind.</p> --}}
                        <div class="flex gap-3 mb-4">
                            <button @click="updateCustomerType('business')"
                                :class="customerType === 'business' ? 'bg-blue-50 text-blue-700 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors flex items-center gap-2">
                                <i class="fas fa-building text-xs"></i> Firmenkunde
                            </button>
                            {{-- <button @click="updateCustomerType('private')"
                                :class="customerType === 'private' ? 'bg-blue-50 text-blue-700 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors flex items-center gap-2">
                                <i class="fas fa-user text-xs"></i> Privatkunde
                            </button> --}}
                        </div>

                        {{-- Geschäftstyp (nur bei Firmenkunde) --}}
                        <div x-show="customerType === 'business'" x-transition x-cloak>
                            <h4 class="text-sm font-semibold text-gray-900 mb-2 mt-2">Geschäftstyp</h4>
                            <p class="text-xs text-gray-500 mb-3">Bitte wählen Sie den Tätigkeitsbereich aus (Mehrfachauswahl möglich).</p>
                            <div class="flex gap-2 flex-wrap">
                                @php
                                    $businessOptions = [
                                        'travel_agency' => 'Reisebüro',
                                        'organizer' => 'Veranstalter',
                                        'online_provider' => 'Online Anbieter',
                                        'mobile_travel_consultant' => 'Mobiler Reiseberater',
                                        'cooperation' => 'Kooperation',
                                        'software_provider' => 'Softwareanbieter',
                                        'other' => 'Sonstiges',
                                    ];
                                @endphp
                                @foreach($businessOptions as $key => $label)
                                <button @click="toggleBusinessType('{{ $key }}')"
                                    :class="isBusinessTypeSelected('{{ $key }}') ? 'bg-blue-50 text-blue-700 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors">
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Firmendaten Übersicht --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Firmendaten</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-xs text-gray-500">Firmenname</span>
                                <p class="font-medium text-gray-900">{{ $customer->company_name ?: '—' }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Anschrift</span>
                                <p class="font-medium text-gray-900">
                                    @if($customer->company_street)
                                        {{ $customer->company_street }} {{ $customer->company_house_number }}<br>
                                        {{ $customer->company_postal_code }} {{ $customer->company_city }}
                                    @else — @endif
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Kontakt</span>
                                <p class="font-medium text-gray-900">{{ $customer->email }}</p>
                                @if($customer->phone)<p class="text-xs text-gray-500">{{ $customer->phone }}</p>@endif
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ==================== Tab: Adressen ==================== --}}
                <div x-show="mdTab === 'adressen'" x-cloak>
                    {{-- Firmenanschrift --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900">Firmenanschrift</h4>
                            <button @click="editSection = editSection === 'company' ? null : 'company'"
                                    class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <i class="fas fa-pen text-[10px]"></i>
                                <span x-text="editSection === 'company' ? 'Abbrechen' : 'Bearbeiten'"></span>
                            </button>
                        </div>
                        <div x-show="editSection !== 'company'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                            <div><span class="text-xs text-gray-500">Firmenname</span><p class="font-medium text-gray-900" x-text="company.company_name || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Zusatz</span><p class="font-medium text-gray-900" x-text="company.company_additional || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Straße / Nr.</span><p class="font-medium text-gray-900" x-text="(company.company_street || '—') + ' ' + (company.company_house_number || '')"></p></div>
                            <div><span class="text-xs text-gray-500">PLZ</span><p class="font-medium text-gray-900" x-text="company.company_postal_code || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Stadt</span><p class="font-medium text-gray-900" x-text="company.company_city || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Land</span><p class="font-medium text-gray-900" x-text="company.company_country || '—'"></p></div>
                        </div>
                        <form x-show="editSection === 'company'" x-cloak @submit.prevent="saveCompany" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Firmenname</label><input type="text" x-model="company.company_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Zusatz</label><input type="text" x-model="company.company_additional" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div class="grid grid-cols-3 gap-2"><div class="col-span-2"><label class="block text-xs font-medium text-gray-700 mb-1">Straße</label><input type="text" x-model="company.company_street" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-xs font-medium text-gray-700 mb-1">Nr.</label><input type="text" x-model="company.company_house_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">PLZ</label><input type="text" x-model="company.company_postal_code" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Stadt</label><input type="text" x-model="company.company_city" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Land</label><input type="text" x-model="company.company_country" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-2 pt-2">
                                <button type="button" @click="editSection = null" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"><i class="fas fa-save mr-1"></i> Speichern</button>
                            </div>
                        </form>
                    </div>

                    {{-- Rechnungsadresse --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900">Rechnungsadresse</h4>
                            <div class="flex items-center gap-3">
                                <button x-show="editSection === 'billing'" x-cloak
                                    @click="billing.billing_company_name = company.company_name; billing.billing_additional = company.company_additional; billing.billing_street = company.company_street; billing.billing_house_number = company.company_house_number; billing.billing_postal_code = company.company_postal_code; billing.billing_city = company.company_city; billing.billing_country = company.company_country;"
                                    class="text-xs text-gray-500 hover:text-blue-600 flex items-center gap-1 transition-colors">
                                    <i class="fas fa-copy text-[10px]"></i>
                                    Aus Firmenanschrift
                                </button>
                                <button @click="editSection = editSection === 'billing' ? null : 'billing'"
                                        class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <i class="fas fa-pen text-[10px]"></i>
                                    <span x-text="editSection === 'billing' ? 'Abbrechen' : 'Bearbeiten'"></span>
                                </button>
                            </div>
                        </div>
                        <div x-show="editSection !== 'billing'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                            <div><span class="text-xs text-gray-500">Firmenname</span><p class="font-medium text-gray-900" x-text="billing.billing_company_name || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Zusatz</span><p class="font-medium text-gray-900" x-text="billing.billing_additional || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Straße / Nr.</span><p class="font-medium text-gray-900" x-text="(billing.billing_street || '—') + ' ' + (billing.billing_house_number || '')"></p></div>
                            <div><span class="text-xs text-gray-500">PLZ</span><p class="font-medium text-gray-900" x-text="billing.billing_postal_code || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Stadt</span><p class="font-medium text-gray-900" x-text="billing.billing_city || '—'"></p></div>
                            <div><span class="text-xs text-gray-500">Land</span><p class="font-medium text-gray-900" x-text="billing.billing_country || '—'"></p></div>
                        </div>
                        <form x-show="editSection === 'billing'" x-cloak @submit.prevent="saveBilling" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Firmenname</label><input type="text" x-model="billing.billing_company_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Zusatz</label><input type="text" x-model="billing.billing_additional" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div class="grid grid-cols-3 gap-2"><div class="col-span-2"><label class="block text-xs font-medium text-gray-700 mb-1">Straße</label><input type="text" x-model="billing.billing_street" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div><div><label class="block text-xs font-medium text-gray-700 mb-1">Nr.</label><input type="text" x-model="billing.billing_house_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">PLZ</label><input type="text" x-model="billing.billing_postal_code" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Stadt</label><input type="text" x-model="billing.billing_city" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="block text-xs font-medium text-gray-700 mb-1">Land</label><input type="text" x-model="billing.billing_country" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-2 pt-2">
                                <button type="button" @click="editSection = null" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"><i class="fas fa-save mr-1"></i> Speichern</button>
                            </div>
                        </form>
                    </div>
                </div>

                @include('customer.settings.partials.tab-phones')

                @include('customer.settings.partials.tab-emails')

                @include('customer.settings.partials.tab-web')

                {{-- ==================== Tab: Abteilungen ==================== --}}
                <div x-show="mdTab === 'abteilungen'" x-cloak>
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs text-gray-500"><span x-text="departments.length"></span> Abteilungen angelegt</p>
                        <button @click="showDeptForm = true; deptEditId = null; deptForm = {name:'',description:'',code:'',is_active:true};"
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs">
                            <i class="fas fa-plus"></i> Neue Abteilung
                        </button>
                    </div>

                    {{-- Add/Edit Form --}}
                    <div x-show="showDeptForm" x-cloak class="bg-white rounded-lg border border-gray-200 mb-5 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                            <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas text-blue-600" :class="deptEditId ? 'fa-pen' : 'fa-folder-plus'"></i>
                                <span x-text="deptEditId ? 'Abteilung bearbeiten' : 'Neue Abteilung anlegen'"></span>
                            </h4>
                        </div>
                        <form @submit.prevent="saveDepartment" class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="deptForm.name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="z.B. Vertrieb">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Kürzel</label>
                                    <input type="text" x-model="deptForm.code" maxlength="20" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="z.B. VT">
                                </div>
                                <div class="sm:col-span-2 lg:col-span-3">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Beschreibung</label>
                                    <input type="text" x-model="deptForm.description" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Kurzbeschreibung der Abteilung">
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-200">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" x-model="deptForm.is_active" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700">Abteilung ist aktiv</span>
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" @click="showDeptForm = false" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                    <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                                        <i class="fas fa-save"></i> <span x-text="deptEditId ? 'Aktualisieren' : 'Speichern'"></span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Loading --}}
                    <div x-show="deptLoading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-gray-400 text-xl"></i>
                    </div>

                    {{-- Empty --}}
                    <template x-if="!deptLoading && departments.length === 0 && !showDeptForm">
                        <div class="bg-white rounded-lg border border-dashed border-gray-300 p-8 text-center">
                            <i class="fas fa-folder-tree text-3xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">Noch keine Abteilungen angelegt.</p>
                            <p class="text-xs text-gray-400 mt-1">Erstellen Sie Ihre erste Abteilung.</p>
                        </div>
                    </template>

                    {{-- Department List --}}
                    <div x-show="!deptLoading && departments.length > 0" class="space-y-3">
                        <template x-for="dept in departments" :key="dept.id">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow drag-item"
                                 draggable="true"
                                 x-on:dragstart="deptDragStart(dept.id)" x-on:dragover="deptDragOver($event)" x-on:drop="deptDrop(dept.id)" x-on:dragend="deptDragId = null"
                                 :class="{ 'dragging': deptDragId === dept.id, 'drag-over': deptDragId !== null && deptDragId !== dept.id }">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3 flex-1">
                                        <div class="drag-handle text-gray-300 hover:text-gray-500 px-1 flex-shrink-0 mt-0.5" title="Ziehen zum Verschieben">
                                            <i class="fas fa-grip-vertical text-xs"></i>
                                        </div>
                                        <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="text-sm font-semibold text-gray-900" x-text="dept.name"></h4>
                                            <span x-show="dept.code" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600 font-mono" x-text="dept.code"></span>
                                            <span x-show="!dept.is_active" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">Inaktiv</span>
                                        </div>
                                        <p x-show="dept.description" class="text-xs text-gray-500" x-text="dept.description"></p>
                                        </div>
                                    </div>
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                            <i class="fas fa-ellipsis-vertical text-sm"></i>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition x-cloak
                                             class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <button @click="moveDepartment(dept.id, -1); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fas fa-chevron-up w-4 text-center text-gray-400"></i> Nach oben
                                            </button>
                                            <button @click="moveDepartment(dept.id, 1); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fas fa-chevron-down w-4 text-center text-gray-400"></i> Nach unten
                                            </button>
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <button @click="editDepartment(dept); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                            </button>
                                            <button @click="deleteDepartment(dept.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                <i class="fas fa-trash w-4 text-center"></i> Löschen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Toast --}}
                <div x-show="message" x-cloak x-transition
                     class="fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg text-sm z-50"
                     :class="messageType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
                     x-text="message"
                     @click="message = null"></div>

                </div>

            @elseif($settingsSection === 'users')
                <div x-data="usersManager()">

                {{-- Tab-Leiste --}}
                <div class="tab-navigation flex border-b border-gray-200 bg-white -mx-6 -mt-6 px-4 mb-6">
                    <button @click="usersTab = 'benutzer'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="usersTab === 'benutzer' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-users mr-2"></i>
                        Benutzer
                    </button>
                    <button @click="usersTab = 'gruppen'; loadGroups();"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="usersTab === 'gruppen' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-layer-group mr-2"></i>
                        Gruppen
                    </button>
                </div>

                {{-- Tab: Benutzer --}}
                <div x-show="usersTab === 'benutzer'">

                    {{-- Header mit Suche und Filter-Toggle --}}
                    <div x-show="!showEmpForm" class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-0.5">Benutzer</h3>
                            <p class="text-xs text-gray-500"><span x-text="filteredEmployees.length"></span> von <span x-text="employees.length"></span> Benutzern</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="showFilter = !showFilter"
                                    class="px-3 py-2 rounded-lg transition-colors flex items-center gap-2 text-xs border"
                                    :class="showFilter || hasActiveFilters ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                                <i class="fas fa-filter"></i>
                                Filter
                                <span x-show="hasActiveFilters" class="w-4 h-4 rounded-full bg-blue-600 text-white text-[9px] flex items-center justify-center" x-text="activeFilterCount"></span>
                            </button>
                            <button @click="showEmpForm = true; empEditId = null; resetEmpForm();"
                                    class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs">
                                <i class="fas fa-plus"></i> Neuer Benutzer
                            </button>
                        </div>
                    </div>

                    <div x-show="!showEmpForm" class="flex gap-4">
                        {{-- Filter Sidebar --}}
                        <div x-show="showFilter" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-4"
                             class="w-64 flex-shrink-0 pt-3">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 sticky top-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Filter</h4>
                                    <button @click="resetFilters()" x-show="hasActiveFilters"
                                            class="text-[10px] text-blue-600 hover:underline">Zurücksetzen</button>
                                </div>

                                <div class="space-y-4">
                                    {{-- Suche --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Suche</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400"><i class="fas fa-search text-[10px]"></i></span>
                                            <input type="text" x-model="filter.search" placeholder="Name, E-Mail, Telefon..."
                                                   class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                        </div>
                                    </div>

                                    {{-- Status --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                        <select x-model="filter.status" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                            <option value="">Alle</option>
                                            <option value="active">Aktiv</option>
                                            <option value="inactive">Inaktiv</option>
                                        </select>
                                    </div>

                                    {{-- Gruppe --}}
                                    <div x-show="availableGroups.length > 0">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Gruppe</label>
                                        <select x-model="filter.group" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                            <option value="">Alle Gruppen</option>
                                            <option value="none">Ohne Gruppe</option>
                                            <template x-for="g in availableGroups" :key="g.id">
                                                <option :value="g.id" x-text="g.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- Abteilung --}}
                                    <div x-show="availableDepartments.length > 0">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Abteilung</label>
                                        <select x-model="filter.department" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                            <option value="">Alle Abteilungen</option>
                                            <template x-for="d in availableDepartments" :key="d.id">
                                                <option :value="d.id" x-text="d.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    {{-- Standort --}}
                                    <div x-show="availableBranches.length > 0">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Standort</label>
                                        <select x-model="filter.branch" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                            <option value="">Alle Standorte</option>
                                            <template x-for="b in availableBranches" :key="b.id">
                                                <option :value="b.id" x-text="b.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Benutzerliste --}}
                        <div class="flex-1 min-w-0">

                    {{-- Loading --}}
                    <div x-show="empLoading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-gray-400 text-xl"></i>
                    </div>

                    {{-- Empty --}}
                    <template x-if="!empLoading && employees.length === 0 && !showEmpForm">
                        <div class="bg-white rounded-lg border border-dashed border-gray-300 p-8 text-center">
                            <i class="fas fa-users text-3xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">Noch keine Benutzer erfasst.</p>
                            <p class="text-xs text-gray-400 mt-1">Fügen Sie Ihren ersten Benutzer hinzu.</p>
                        </div>
                    </template>

                    {{-- No results after filter --}}
                    <div x-show="!empLoading && employees.length > 0 && filteredEmployees.length === 0" class="bg-white rounded-lg border border-dashed border-gray-300 p-8 text-center">
                        <i class="fas fa-filter-circle-xmark text-3xl text-gray-300 mb-2"></i>
                        <p class="text-sm text-gray-500">Keine Benutzer gefunden.</p>
                        <p class="text-xs text-gray-400 mt-1">Passen Sie Ihre Filterkriterien an.</p>
                        <button @click="resetFilters()" class="mt-3 text-xs text-blue-600 hover:underline">Filter zurücksetzen</button>
                    </div>

                    {{-- User List --}}
                    <div x-show="!empLoading && filteredEmployees.length > 0" class="space-y-3">
                        <template x-for="emp in filteredEmployees" :key="emp.id">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <h4 class="text-sm font-semibold text-gray-900">
                                                <span x-show="emp.salutation" x-text="emp.salutation === 'herr' ? 'Herr' : emp.salutation === 'frau' ? 'Frau' : ''"></span>
                                                <span x-show="emp.title" x-text="emp.title"></span>
                                                <span x-text="emp.first_name + ' ' + emp.last_name"></span>
                                            </h4>
                                            <span x-show="emp.is_owner" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-800">
                                                <i class="fas fa-crown mr-1 text-[8px]"></i>Eigenes Profil
                                            </span>
                                            @if($isEmployeeLogin)
                                            <span x-show="emp.id === {{ $loggedInEmployee->id }}" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-user-check mr-1 text-[8px]"></i>Ich
                                            </span>
                                            @endif
                                            <span x-show="!emp.is_currently_active && !emp.is_owner" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">Inaktiv</span>
                                            <span x-show="emp.active_from || emp.active_until" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700">
                                                <i class="fas fa-clock mr-1 text-[8px]"></i>
                                                <span x-text="(emp.active_from ? new Date(emp.active_from).toLocaleDateString('de-DE') : '∞') + ' – ' + (emp.active_until ? new Date(emp.active_until).toLocaleDateString('de-DE') : '∞')"></span>
                                            </span>
                                            <span x-show="emp.position && !emp.is_owner" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800" x-text="emp.position"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span x-show="emp.department_relation || emp.department"><i class="fas fa-folder mr-1"></i><span x-text="emp.department_relation ? emp.department_relation.name : emp.department"></span></span>
                                            <span x-show="emp.email"><i class="fas fa-envelope mr-1"></i><span x-text="emp.email"></span></span>
                                            <span x-show="emp.phone"><i class="fas fa-phone mr-1"></i><span x-text="emp.phone"></span></span>
                                            <span x-show="emp.mobile"><i class="fas fa-mobile-screen mr-1"></i><span x-text="emp.mobile"></span></span>
                                            <span x-show="emp.branch"><i class="fas fa-building mr-1"></i><span x-text="emp.branch?.name"></span></span>
                                            <span x-show="emp.personnel_number"><i class="fas fa-id-badge mr-1"></i><span x-text="emp.personnel_number"></span></span>
                                        </div>
                                        <div x-show="emp.groups && emp.groups.length > 0" class="flex flex-wrap gap-1 mt-1">
                                            <template x-for="g in (emp.groups || [])" :key="g.id">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800">
                                                    <i class="fas fa-layer-group mr-1 text-[8px]"></i><span x-text="g.name"></span>
                                                </span>
                                            </template>
                                        </div>
                                        <p x-show="emp.notes" class="text-xs text-gray-400 mt-1 italic line-clamp-1" x-text="emp.notes"></p>
                                    </div>
                                    <div class="flex items-center gap-1" x-show="!emp.is_owner">
                                        <button @click="editEmployee(emp)" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition-colors" title="Bearbeiten">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <button @click="deleteEmployee(emp.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 transition-colors" title="Löschen">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                    <div x-show="emp.is_owner" class="flex items-center">
                                        <a href="{{ route('customer.settings', ['section' => 'general']) }}" class="text-xs text-blue-600 hover:underline">Profil bearbeiten</a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                        </div>{{-- Ende Benutzerliste --}}
                    </div>{{-- Ende flex gap-4 --}}

                    {{-- Add/Edit Form (eigenständige Ansicht) --}}
                    <div x-show="showEmpForm" x-cloak class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas" :class="empEditId ? 'fa-pen' : 'fa-user-plus'" style="color: #2563eb;"></i>
                                <span x-text="empEditId ? 'Benutzer bearbeiten' : 'Neuen Benutzer erfassen'"></span>
                            </h4>
                            <button type="button" @click="showEmpForm = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors" title="Schließen">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form @submit.prevent="saveEmployee" class="p-5">
                            {{-- Sektion: Persönliche Daten --}}
                            <div class="mb-5">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-user text-gray-400"></i> Persönliche Daten
                                </h5>
                                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Anrede</label>
                                        <select x-model="empForm.salutation" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">-- Bitte wählen --</option>
                                            <option value="herr">Herr</option>
                                            <option value="frau">Frau</option>
                                            <option value="divers">Divers</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Titel</label>
                                        <select x-model="empForm.title" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">-- Keiner --</option>
                                            <option value="Dr.">Dr.</option>
                                            <option value="Prof.">Prof.</option>
                                            <option value="Prof. Dr.">Prof. Dr.</option>
                                            <option value="Dipl.-Ing.">Dipl.-Ing.</option>
                                            <option value="Dipl.-Kfm.">Dipl.-Kfm.</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Vorname <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="empForm.first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Vorname">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Nachname <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="empForm.last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Nachname">
                                    </div>
                                </div>
                            </div>

                            {{-- Sektion: Kontaktdaten --}}
                            <div class="mb-5">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-address-book text-gray-400"></i> Kontaktdaten
                                </h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">E-Mail</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-envelope text-xs"></i></span>
                                            <input type="email" x-model="empForm.email" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="name@firma.de">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Telefon</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-phone text-xs"></i></span>
                                            <input type="text" x-model="empForm.phone" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="+49 ...">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Mobilnummer</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-mobile-screen text-xs"></i></span>
                                            <input type="text" x-model="empForm.mobile" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="+49 1...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sektion: Organisation --}}
                            <div class="mb-5">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-sitemap text-gray-400"></i> Organisation
                                </h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Position</label>
                                        <input type="text" x-model="empForm.position" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="z.B. Reiseberater">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Abteilung</label>
                                        <select x-model="empForm.department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">-- Keine Abteilung --</option>
                                            <template x-for="d in availableDepartments" :key="d.id">
                                                <option :value="d.id" x-text="d.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Personalnummer</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-id-badge text-xs"></i></span>
                                            <input type="text" x-model="empForm.personnel_number" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Optional">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Standort</label>
                                        <select x-model="empForm.branch_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">-- Kein Standort --</option>
                                            <template x-for="b in availableBranches" :key="b.id">
                                                <option :value="b.id" x-text="b.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Sektion: Gruppen --}}
                            <div class="mb-5" x-show="availableGroups.length > 0">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-layer-group text-gray-400"></i> Gruppen
                                </h5>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="g in availableGroups" :key="g.id">
                                        <label class="flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-all border text-sm"
                                               :class="empForm.group_ids.includes(g.id) ? 'bg-blue-50 border-blue-400' : 'bg-gray-50 border-gray-200 hover:border-gray-300'">
                                            <input type="checkbox" class="sr-only"
                                                   :checked="empForm.group_ids.includes(g.id)"
                                                   @change="empForm.group_ids.includes(g.id) ? empForm.group_ids = empForm.group_ids.filter(v => v !== g.id) : empForm.group_ids.push(g.id)">
                                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                                 :class="empForm.group_ids.includes(g.id) ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                                                <i x-show="empForm.group_ids.includes(g.id)" class="fas fa-check text-[8px] text-white"></i>
                                            </div>
                                            <span class="text-gray-700" x-text="g.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>

                            {{-- Sektion: Notiz --}}
                            <div class="mb-5">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-sticky-note text-gray-400"></i> Notiz
                                </h5>
                                <textarea x-model="empForm.notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Interne Anmerkungen zu diesem Benutzer..."></textarea>
                            </div>

                            {{-- Sektion: Status & Aktivitätszeitraum --}}
                            <div class="mb-5">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-clock text-gray-400"></i> Status & Aktivitätszeitraum
                                </h5>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="flex items-center">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" x-model="empForm.is_active" class="sr-only peer">
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                            </div>
                                            <span class="text-xs font-medium text-gray-700">Benutzer ist aktiv</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Aktiv von</label>
                                        <input type="date" x-model="empForm.active_from"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Aktiv bis</label>
                                        <input type="date" x-model="empForm.active_until"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-2">Ohne Datumsangabe ist der Benutzer dauerhaft aktiv.</p>
                            </div>

                            {{-- Buttons --}}
                            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-200">
                                <button type="button" @click="showEmpForm = false" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                                    <i class="fas fa-save"></i> <span x-text="empEditId ? 'Aktualisieren' : 'Speichern'"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
                {{-- Ende Tab: Benutzer --}}

                {{-- Tab: Gruppen --}}
                <div x-show="usersTab === 'gruppen'" x-cloak>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Gruppen</h3>
                    <p class="text-sm text-gray-500 mb-4">Erstellen Sie Benutzergruppen, um Ihre Mitarbeiter zu organisieren.</p>

                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs text-gray-500"><span x-text="groups.length"></span> Gruppen erfasst</p>
                        <button @click="showGroupForm = true; groupEditId = null; resetGroupForm();"
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs">
                            <i class="fas fa-plus"></i> Neue Gruppe
                        </button>
                    </div>

                    {{-- Add/Edit Group Form --}}
                    <div x-show="showGroupForm" x-cloak class="bg-white rounded-lg border border-gray-200 mb-5 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                            <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas" :class="groupEditId ? 'fa-pen' : 'fa-layer-group'" style="color: #2563eb;"></i>
                                <span x-text="groupEditId ? 'Gruppe bearbeiten' : 'Neue Gruppe erstellen'"></span>
                            </h4>
                        </div>
                        <form @submit.prevent="saveGroup()" class="p-5">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="groupForm.name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="z.B. Marketing-Team">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Beschreibung</label>
                                    <textarea x-model="groupForm.description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Optionale Beschreibung der Gruppe..."></textarea>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-200">
                                <button type="button" @click="showGroupForm = false" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                                    <i class="fas fa-save"></i> <span x-text="groupEditId ? 'Aktualisieren' : 'Speichern'"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Loading --}}
                    <div x-show="groupLoading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-gray-400 text-xl"></i>
                    </div>

                    {{-- Empty --}}
                    <template x-if="!groupLoading && groups.length === 0 && !showGroupForm">
                        <div class="bg-white rounded-lg border border-dashed border-gray-300 p-8 text-center">
                            <i class="fas fa-layer-group text-3xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">Noch keine Gruppen erstellt.</p>
                            <p class="text-xs text-gray-400 mt-1">Erstellen Sie Ihre erste Benutzergruppe.</p>
                        </div>
                    </template>

                    {{-- Group List --}}
                    <div x-show="!groupLoading && groups.length > 0" class="space-y-3">
                        <template x-for="group in groups" :key="group.id">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="text-sm font-semibold text-gray-900" x-text="group.name"></h4>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800">
                                                <span x-text="group.employees_count"></span>&nbsp;Benutzer
                                            </span>
                                            <span x-show="group.is_system" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-lock mr-1 text-[8px]"></i>System
                                            </span>
                                        </div>
                                        <p x-show="group.description" class="text-xs text-gray-500" x-text="group.description"></p>
                                    </div>
                                    <div class="flex items-center gap-1" x-show="!group.is_system">
                                        <button @click="editGroup(group)" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition-colors" title="Bearbeiten">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <button @click="deleteGroup(group.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 transition-colors" title="Löschen">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                {{-- Ende Tab: Gruppen --}}

                </div>

            @elseif($settingsSection === 'organization')
                <div x-data="organizationManager()" x-on:org-toggle-node.window="toggleOrgNode($event.detail.id)" x-on:org-update-data.window="updateNodeData($event.detail.id, $event.detail.field, $event.detail.value)">

                {{-- Tab-Leiste --}}
                <div class="tab-navigation flex border-b border-gray-200 bg-white -mx-6 -mt-6 px-4 mb-6">
                    <button @click="orgTab = 'uebersicht'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="orgTab === 'uebersicht' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-gauge-high mr-2"></i>
                        Übersicht
                    </button>
                    <button @click="orgTab = 'struktur'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="orgTab === 'struktur' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-sitemap mr-2"></i>
                        Struktur
                    </button>
                    @php
                        $assignedCount = \App\Models\Customer::where('assign_to', $customer->id)->count();
                        $activeAssignedCount = \App\Models\Customer::where('assign_to', $customer->id)
                            ->whereHas('legacyOptions', function ($q) {
                                $q->where(function ($q2) {
                                    $q2->whereNull('live_from')->orWhere('live_from', '<=', now()->toDateString());
                                })->where(function ($q2) {
                                    $q2->whereNull('end_of_use')->orWhere('end_of_use', '>=', now()->toDateString());
                                });
                            })->count();
                    @endphp
                    @if($assignedCount > 0)
                    <button @click="orgTab = 'agenturen'"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="orgTab === 'agenturen' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i class="fas fa-store mr-2"></i>
                        Agenturen <span class="ml-1 px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">{{ $activeAssignedCount }}</span>
                    </button>
                    @endif
                </div>

                {{-- ==================== Tab: Übersicht ==================== --}}
                <div x-show="orgTab === 'uebersicht'">
                    @php
                        $branchCount = \App\Models\Branch::where('customer_id', $customer->id)->count();
                        $employeeCount = \App\Models\Employee::where('customer_id', $customer->id)->count();
                        $deptCount = \App\Models\Department::where('customer_id', $customer->id)->count();
                        $assignedToCustomer = $customer->assign_to ? \App\Models\Customer::find($customer->assign_to) : null;
                    @endphp

                    {{-- Zugeordnete Organisation --}}
                    @if($assignedToCustomer)
                    <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <i class="fas fa-building text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-2">Zugeordnete Organisation</h4>
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                                    <div>
                                        <span class="text-xs text-blue-600 block">Firma</span>
                                        <span class="text-gray-900 font-medium">{{ $assignedToCustomer->company_name ?? '—' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-blue-600 block">App-Code</span>
                                        <span class="font-mono text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">{{ $assignedToCustomer->app_code }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-blue-600 block">Adresse</span>
                                        <span class="text-gray-900">{{ trim(($assignedToCustomer->company_street ?? '') . ' ' . ($assignedToCustomer->company_house_number ?? '')) ?: '—' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-blue-600 block">PLZ / Ort</span>
                                        <span class="text-gray-900">{{ trim(($assignedToCustomer->company_postal_code ?? '') . ' ' . ($assignedToCustomer->company_city ?? '')) ?: '—' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-blue-600 block">Land</span>
                                        <span class="text-gray-900">{{ $assignedToCustomer->company_country ?? '—' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-blue-600 block">E-Mail</span>
                                        <span class="text-gray-900">{{ $assignedToCustomer->email ?? '—' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-blue-600 block">Telefon</span>
                                        <span class="text-gray-900">{{ $assignedToCustomer->phone ?? '—' }}</span>
                                    </div>
                                </div>
                                @if($isSuperAdmin)
                                <div class="mt-3 pt-3 border-t border-blue-200">
                                    <form method="POST" action="{{ route('customer.account.switch', $assignedToCustomer) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs">
                                            <i class="fas fa-arrows-repeat"></i> Zu Agentur wechseln
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Header mit Neu-Button --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 flex-1 mr-4">
                            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                                <p class="text-lg font-bold text-gray-900">{{ $branchCount }}</p>
                                <p class="text-[10px] text-gray-500">Adressen</p>
                            </div>
                            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                                <p class="text-lg font-bold text-gray-900">{{ $employeeCount }}</p>
                                <p class="text-[10px] text-gray-500">Benutzer</p>
                            </div>
                            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                                <p class="text-lg font-bold text-gray-900">{{ $deptCount }}</p>
                                <p class="text-[10px] text-gray-500">Abteilungen</p>
                            </div>
                        </div>
                        <button @click="showNewForm = true" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs flex-shrink-0">
                            <i class="fas fa-plus"></i> Neue Adresse
                        </button>
                    </div>

                    @include('customer.settings.partials.branch-form')

                    {{-- Adressen-Liste --}}
                    <div x-show="loading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-gray-400 text-xl"></i>
                    </div>

                    <template x-if="!loading && branches.length === 0 && !showNewForm">
                        <div class="bg-white rounded-lg border border-dashed border-gray-300 p-8 text-center">
                            <i class="fas fa-building text-3xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-500">Noch keine Adressen angelegt.</p>
                            <p class="text-xs text-gray-400 mt-1">Fügen Sie Ihre erste Adresse hinzu.</p>
                        </div>
                    </template>

                    <div x-show="!loading && branches.length > 0" class="space-y-3">
                        <template x-for="branch in branches" :key="branch.id">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <h4 class="text-sm font-semibold text-gray-900" x-text="branch.name"></h4>
                                            <span x-show="branch.is_headquarters" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-crown mr-1 text-[8px]"></i>Hauptsitz
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 mb-1">
                                            <span>
                                                <i class="fas fa-location-dot mr-1"></i>
                                                <span x-text="(branch.street || '') + ' ' + (branch.house_number || '') + ', ' + (branch.postal_code || '') + ' ' + (branch.city || '')"></span>
                                            </span>
                                            <span x-show="branch.country"><i class="fas fa-globe mr-1"></i><span x-text="branch.country"></span></span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span x-show="(branch.phone_numbers || []).length > 0">
                                                <i class="fas fa-phone mr-1"></i><span x-text="branch.phone_numbers[0]?.number"></span>
                                                <span x-show="branch.phone_numbers.length > 1" class="text-gray-400" x-text="'(+' + (branch.phone_numbers.length - 1) + ')'"></span>
                                            </span>
                                            <span x-show="(branch.email_addresses || []).length > 0">
                                                <i class="fas fa-envelope mr-1"></i><span x-text="branch.email_addresses[0]?.email"></span>
                                                <span x-show="branch.email_addresses.length > 1" class="text-gray-400" x-text="'(+' + (branch.email_addresses.length - 1) + ')'"></span>
                                            </span>
                                            <span x-show="(branch.websites || []).length > 0">
                                                <i class="fas fa-globe mr-1"></i><span x-text="branch.websites[0]?.url"></span>
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            <template x-for="on in (branch.org_nodes || [])" :key="on.id">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-50 text-purple-700" x-text="on.name"></span>
                                            </template>
                                            <span x-show="(branch.contacts || []).length" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-address-card mr-1 text-[8px]"></i><span x-text="branch.contacts.length"></span>&nbsp;Kontakte
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <button @click="editExistingBranch(branch)" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition-colors" title="Bearbeiten">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <button x-show="!branch.is_headquarters" @click="deleteBranch(branch.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 transition-colors" title="Löschen">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ==================== Tab: Struktur ==================== --}}
                <div x-show="orgTab === 'struktur'" x-cloak>
                    @include('customer.settings.partials.tab-org-chart')
                </div>

                {{-- ==================== Tab: Agenturen ==================== --}}
                @if($assignedCount > 0)
                <div x-show="orgTab === 'agenturen'" x-cloak x-data="assignedAgencies()">
                    {{-- Suche --}}
                    <div class="mb-4">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search text-xs"></i></span>
                            <input type="text" x-model="search" @input.debounce.300ms="loadAgencies()" @keydown.escape="search = ''; loadAgencies()"
                                   placeholder="Suche nach Firma, Code, PLZ, Ort..."
                                   class="w-full pl-9 pr-9 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button x-show="search.length > 0" @click="search = ''; loadAgencies()" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times-circle text-sm"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Tabelle --}}
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Code</th>
                                    <th @click="toggleSort('company_name')" class="px-4 py-2 text-left text-xs font-semibold text-gray-600 cursor-pointer select-none hover:text-blue-600">
                                        Firma
                                        <i class="fas ml-1" :class="sortField === 'company_name' ? (sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort text-gray-300'"></i>
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Straße</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">PLZ</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Ort</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="loading">
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Laden...</td></tr>
                                </template>
                                <template x-if="!loading && filteredAgencies.length === 0">
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Keine Agenturen gefunden</td></tr>
                                </template>
                                <template x-for="agency in filteredAgencies" :key="agency.id">
                                    <tr class="border-b border-gray-100"
                                        :class="isInactive(agency) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50'">
                                        <td class="px-4 py-2"><span class="font-mono text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded" x-text="agency.app_code"></span></td>
                                        <td class="px-4 py-2 font-medium" x-text="agency.company_name"></td>
                                        <td class="px-4 py-2 text-gray-600" x-text="agency.company_street || '—'"></td>
                                        <td class="px-4 py-2 text-gray-600" x-text="agency.company_postal_code || '—'"></td>
                                        <td class="px-4 py-2 text-gray-600" x-text="agency.company_city || '—'"></td>
                                        <td class="px-4 py-2 text-right">
                                            <div class="relative" x-data="{ menuOpen: false }">
                                                <button @click="menuOpen = !menuOpen" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <div x-show="menuOpen" @click.away="menuOpen = false" x-cloak
                                                     class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">
                                                    <button @click="menuOpen = false; showAgencyDetail(agency)"
                                                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                        <i class="fas fa-eye text-xs text-gray-400"></i> Details anzeigen
                                                    </button>
                                                    @if($isSuperAdmin)
                                                    <form method="POST" :action="'/customer/account/switch/' + agency.id">
                                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                        <button type="submit"
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                            <i class="fas fa-arrows-repeat text-xs text-gray-400"></i> Zur Agentur wechseln
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Detail Modal --}}
                    <div x-show="detailAgency" x-cloak class="fixed inset-0 z-[10000] flex items-center justify-center" @keydown.escape.window="detailAgency = null">
                        <div class="absolute inset-0 bg-black/50" @click="detailAgency = null"></div>
                        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[80vh] overflow-y-auto" x-data="{ detailTab: 'kontakt' }">
                            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                                <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                    <i class="fas fa-building text-blue-600"></i>
                                    <span x-text="detailAgency?.company_name"></span>
                                    <span class="font-mono text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded" x-text="detailAgency?.app_code"></span>
                                </h3>
                                <button @click="detailAgency = null" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            {{-- Tabs --}}
                            <div class="flex border-b border-gray-200 px-5">
                                <button @click="detailTab = 'kontakt'"
                                        :class="detailTab === 'kontakt' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                        class="px-3 py-2 text-xs font-medium border-b-2 transition-colors">
                                    Kontakt & Adresse
                                </button>
                                @if($isSuperAdmin)
                                <button @click="detailTab = 'legacy'"
                                        :class="detailTab === 'legacy' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                        class="px-3 py-2 text-xs font-medium border-b-2 transition-colors">
                                    Legacy Optionen
                                </button>
                                @endif
                            </div>

                            {{-- Tab: Kontakt & Adresse --}}
                            <div x-show="detailTab === 'kontakt'" class="p-5 space-y-4">
                                {{-- Kontakt --}}
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Kontakt</h4>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span class="text-xs text-gray-500 block">Name</span>
                                            <span class="text-gray-900" x-text="detailAgency?.name || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">E-Mail</span>
                                            <span class="text-gray-900" x-text="detailAgency?.email || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">Telefon</span>
                                            <span class="text-gray-900" x-text="detailAgency?.phone || '—'"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Firmenadresse --}}
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Firmenadresse</h4>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="col-span-2">
                                            <span class="text-xs text-gray-500 block">Firma</span>
                                            <span class="text-gray-900" x-text="detailAgency?.company_name || '—'"></span>
                                        </div>
                                        <div class="col-span-2" x-show="detailAgency?.company_additional">
                                            <span class="text-xs text-gray-500 block">Zusatz</span>
                                            <span class="text-gray-900" x-text="detailAgency?.company_additional"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">Straße</span>
                                            <span class="text-gray-900" x-text="(detailAgency?.company_street || '') + ' ' + (detailAgency?.company_house_number || '') || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">PLZ / Ort</span>
                                            <span class="text-gray-900" x-text="(detailAgency?.company_postal_code || '') + ' ' + (detailAgency?.company_city || '') || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">Land</span>
                                            <span class="text-gray-900" x-text="detailAgency?.company_country || '—'"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Rechnungsadresse --}}
                                <div x-show="detailAgency?.billing_company_name || detailAgency?.billing_street">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rechnungsadresse</h4>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="col-span-2">
                                            <span class="text-xs text-gray-500 block">Firma</span>
                                            <span class="text-gray-900" x-text="detailAgency?.billing_company_name || '—'"></span>
                                        </div>
                                        <div class="col-span-2" x-show="detailAgency?.billing_additional">
                                            <span class="text-xs text-gray-500 block">Zusatz</span>
                                            <span class="text-gray-900" x-text="detailAgency?.billing_additional"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">Straße</span>
                                            <span class="text-gray-900" x-text="(detailAgency?.billing_street || '') + ' ' + (detailAgency?.billing_house_number || '') || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">PLZ / Ort</span>
                                            <span class="text-gray-900" x-text="(detailAgency?.billing_postal_code || '') + ' ' + (detailAgency?.billing_city || '') || '—'"></span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 block">Land</span>
                                            <span class="text-gray-900" x-text="detailAgency?.billing_country || '—'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab: Legacy Optionen (nur für Super-Admins) --}}
                            @if($isSuperAdmin)
                            <div x-show="detailTab === 'legacy'" class="p-5 space-y-4">
                                <template x-if="!detailAgency?.legacy_options">
                                    <div class="text-sm text-gray-500 text-center py-4">Keine Legacy-Optionen vorhanden</div>
                                </template>
                                <template x-if="detailAgency?.legacy_options">
                                    <div class="space-y-4">
                                        {{-- Status --}}
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Status</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Revised</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.revised ? 'Ja' : 'Nein'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Account Typ</span>
                                                    <span class="text-gray-900" x-text="({'1':'Testaccount VA','2':'Testaccount RB','3':'Veranstalter','4':'Reisebüro','5':'Reiseberater'})[detailAgency.legacy_options.account_type] || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Client Typ</span>
                                                    <span class="text-gray-900" x-text="({'1':'Client','2':'Lead'})[detailAgency.legacy_options.client_type] || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Anzahl Büros</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.office_count ?? '—'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Zeitraum --}}
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Zeitraum</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Live ab</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.live_from ? new Date(detailAgency.legacy_options.live_from).toLocaleDateString('de-DE') : '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Nutzungsende</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.end_of_use ? new Date(detailAgency.legacy_options.end_of_use).toLocaleDateString('de-DE') : '—'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Visa Service --}}
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Visa Service</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Visa Service anzeigen</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.show_visa_service ? 'Ja' : 'Nein'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Visa Orte</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.visa_places || '—'"></span>
                                                </div>
                                                <div class="col-span-2" x-show="detailAgency.legacy_options.show_visa_service_link">
                                                    <span class="text-xs text-gray-500 block">Visa Service Link</span>
                                                    <span class="text-gray-900 break-all" x-text="detailAgency.legacy_options.show_visa_service_link"></span>
                                                </div>
                                                <div class="col-span-2" x-show="detailAgency.legacy_options.show_visa_service_text">
                                                    <span class="text-xs text-gray-500 block">Visa Service Text</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.show_visa_service_text"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Reisewarnung --}}
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Reisewarnung</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Reisewarnung anzeigen</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.show_travel_warning ? 'Ja' : 'Nein'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Land</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.travel_warning_country || '—'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- API & Technik --}}
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">API & Technik</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-xs text-gray-500 block">API Version</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.response_api_version || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">API Status</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.response_api_status ?? '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Tech Access</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.tech_access ?? '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Report</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.use_report ?? '—'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Sonstiges (nur für Super-Admins) --}}
                                        @if($isSuperAdmin)
                                        <div>
                                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Sonstiges</h4>
                                            <div class="grid grid-cols-2 gap-3 text-sm">
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Zoho CRM ID</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.zoho_crm_id || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">MyJack Agency ID</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.myjack_agency_id || '—'"></span>
                                                </div>
                                                <div>
                                                    <span class="text-xs text-gray-500 block">Adressposition</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.agency_address_position ?? '—'"></span>
                                                </div>
                                                <div class="col-span-2" x-show="detailAgency.legacy_options.note">
                                                    <span class="text-xs text-gray-500 block">Notiz</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.note"></span>
                                                </div>
                                                <div x-show="detailAgency.legacy_options.logo">
                                                    <span class="text-xs text-gray-500 block">Logo</span>
                                                    <span class="text-gray-900" x-text="detailAgency.legacy_options.logo"></span>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </template>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Pagination --}}
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs text-gray-500" x-text="filteredAgencies.length + ' Agenturen'"></span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500" x-text="'Seite ' + page + ' von ' + lastPage"></span>
                            <button @click="prevPage()" :disabled="page <= 1"
                                    class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button @click="nextPage()" :disabled="page >= lastPage"
                                    class="px-2 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                </div>

            @elseif($settingsSection === 'travel-requirements')
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Travel Requirements Service</h3>
                <p class="text-sm text-gray-500 mb-6">Verwalten Sie den Zugang zum Travel Requirements Service.</p>

                @php
                    $passolutionService = app(\App\Services\PassolutionService::class);
                    $hasActiveToken = $customer->hasAnyActiveToken();
                    $tokenSource = $customer->getActiveTokenSource();
                @endphp

                <div class="bg-white rounded-lg border border-gray-200 p-5" x-data="{ showTokenInput: false }">
                    <div class="flex items-start gap-4">
                        <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-passport text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">Produktaktivierung</p>
                            <p class="text-xs text-gray-500 mt-1">Aktivieren Sie den Travel Requirements Service, um auf aktuelle Einreisebestimmungen, Visaanforderungen, gesundheitliche Hinweise und umfassende Länderinformationen zuzugreifen. Die Daten können per API oder direkt über die Plattform in Ihre Prozesse integriert werden.</p>
                        </div>
                        <div class="w-32 flex-shrink-0 flex justify-end">
                            @if($hasActiveToken)
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                    <i class="fas fa-check-circle mr-1.5"></i> Aktiv
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                    <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Verbindung aktivieren --}}
                    @if(!$hasActiveToken)
                        <div class="mt-4 ml-12 mr-36">
                            <p class="text-xs text-gray-500 mb-3">Verbindung aktivieren:</p>
                            <div class="flex gap-2">
                                <a href="{{ route('customer.passolution.authorize') }}"
                                   class="px-4 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 flex items-center gap-1.5">
                                    <i class="fas fa-link"></i> Via OAuth verbinden
                                </a>
                                <button @click="showTokenInput = !showTokenInput"
                                        class="px-4 py-2 bg-white text-gray-700 text-xs rounded-lg border border-gray-300 hover:bg-gray-50 flex items-center gap-1.5">
                                    <i class="fas fa-key"></i> Produktschlüssel eingeben
                                </button>
                            </div>
                            <div x-show="showTokenInput" x-cloak x-transition class="mt-3">
                                <form method="POST" action="{{ route('customer.passolution.store-token') }}">
                                    @csrf
                                    <div class="flex gap-2">
                                        <input type="text" name="passolution_token"
                                               placeholder="Token hier einfügen..."
                                               required minlength="10"
                                               class="flex-1 px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <button type="submit"
                                                class="px-4 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 whitespace-nowrap">
                                            Token speichern
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- Verbindungsdetails --}}
                    @if($hasActiveToken)
                        <div class="mt-4 ml-12 mr-36 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-600">Verbindung:</span>
                                <div class="flex items-center gap-2">
                                    @if($tokenSource === 'sso')
                                        <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded border border-blue-200">via SSO</span>
                                    @elseif($customer->passolution_refresh_token)
                                        <span class="px-2 py-1 bg-purple-50 text-purple-700 text-xs font-medium rounded border border-purple-200">via OAuth</span>
                                    @else
                                        <span class="px-2 py-1 bg-amber-50 text-amber-700 text-xs font-medium rounded border border-amber-200">via Token</span>
                                    @endif
                                </div>
                            </div>

                            @if($tokenSource === 'sso' && $customer->pds_api_token_expires_at)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Gültig bis:</span>
                                    <span class="text-xs text-gray-500">{{ $customer->pds_api_token_expires_at->format('d.m.Y H:i') }}</span>
                                </div>
                            @elseif($tokenSource === 'oauth' && $customer->passolution_token_expires_at)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Gültig bis:</span>
                                    <span class="text-xs text-gray-500">{{ $customer->passolution_token_expires_at->format('d.m.Y H:i') }}</span>
                                </div>
                            @endif

                            @if($customer->passolution_subscription_type)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Abonnement:</span>
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded border border-blue-200">
                                        {{ ucfirst($customer->passolution_subscription_type) }}
                                    </span>
                                </div>
                            @endif

                            {{-- Verbundener Account --}}
                            @if($customer->pds_customer_number)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">KID:</span>
                                    <span class="text-xs text-gray-500 font-mono">{{ $customer->pds_customer_number }}</span>
                                </div>
                            @endif



                            @if($tokenSource === 'oauth')
                                <div class="pt-2 border-t border-gray-200">
                                    <form method="POST" action="{{ route('customer.passolution.disconnect') }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                onclick="return confirm('Möchten Sie die Verbindung wirklich trennen?')"
                                                class="px-4 py-2 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 flex items-center gap-1.5">
                                            <i class="fas fa-trash"></i> Verbindung trennen
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Refresh Button --}}
                @if($hasActiveToken)
                    <div class="flex justify-end mt-4" x-data="{ syncing: false, syncMsg: '' }">
                        <button @click="async () => {
                            syncing = true; syncMsg = '';
                            try {
                                const r = await fetch('{{ route('customer.settings.sync-features') }}', {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                });
                                const d = await r.json();
                                syncMsg = d.message;
                                if (d.success) setTimeout(() => location.reload(), 800);
                            } catch(e) { syncMsg = 'Verbindungsfehler.'; }
                            syncing = false;
                        }" :disabled="syncing"
                                class="inline-flex items-center px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 gap-1.5">
                            <i class="fas" :class="syncing ? 'fa-spinner fa-spin' : 'fa-arrows-rotate'"></i>
                            <span x-text="syncing ? 'Wird aktualisiert...' : 'Features aktualisieren'"></span>
                        </button>
                        <span x-show="syncMsg" x-cloak x-text="syncMsg" class="ml-3 text-xs text-gray-500 self-center"></span>
                    </div>
                @endif

                {{-- Freigeschaltete Funktionen als einzelne Sections --}}
                @php
                    $activeFeatures = $customer->passolution_features ?? [];
                    $featureCards = [
                        'content.country' => ['icon' => 'fa-earth-americas', 'label' => 'Länder-Inhalte', 'desc' => 'Zugriff auf umfassende Länderinformationen mit Einreisebestimmungen, Visaanforderungen, Gesundheitshinweisen und Sicherheitsbewertungen.'],
                        'content.cruise' => ['icon' => 'fa-ship', 'label' => 'Kreuzfahrt-Inhalte', 'desc' => 'Informationen zu Kreuzfahrtrouten, Häfen, Einreisebestimmungen für Kreuzfahrtreisende und hafenbezogene Sicherheitshinweise.'],
                        'content.individual' => ['icon' => 'fa-user-pen', 'label' => 'Individuelle Inhalte', 'desc' => 'Erstellung und Verwaltung eigener Inhalte und Informationen, die individuell für Ihre Reisenden bereitgestellt werden.'],
                        'content.tour_operator' => ['icon' => 'fa-building', 'label' => 'Veranstalter-Inhalte', 'desc' => 'Zugriff auf veranstalterspezifische Inhalte mit Pauschalreise-Informationen, Hoteldetails und Zielgebietsbeschreibungen.'],
                        'customer.send_emails' => ['icon' => 'fa-envelope', 'label' => 'E-Mail versenden', 'desc' => 'Versand von E-Mails mit Reiseinformationen, Einreisebestimmungen und Sicherheitshinweisen direkt an Ihre Reisenden.'],
                        'customer.travel_detail_link.create' => ['icon' => 'fa-link', 'label' => 'Reisedetail-Links erstellen', 'desc' => 'Erstellung personalisierter Reisedetail-Links mit individuellen Einreisebestimmungen und Informationen für jeden Reisenden.'],
                        'customer.travel_detail_link.manage' => ['icon' => 'fa-sliders', 'label' => 'Reisedetail-Links verwalten', 'desc' => 'Verwaltung, Bearbeitung und Überwachung bestehender Reisedetail-Links mit Zugriffsstatistiken und Konfigurationsoptionen.'],
                        'customer.travel_detail_link.advert.manage' => ['icon' => 'fa-bullhorn', 'label' => 'Werbung verwalten', 'desc' => 'Integration und Verwaltung von Werbeinhalten und Partnerbannern in Ihren Reisedetail-Links.'],
                        'customer.travel_detail_link.email_subscriptions' => ['icon' => 'fa-bell', 'label' => 'E-Mail Abonnements', 'desc' => 'Verwaltung von E-Mail-Abonnements für automatische Benachrichtigungen bei Änderungen der Einreisebestimmungen.'],
                        'customer.travel_detail_link.inspiration.manage' => ['icon' => 'fa-lightbulb', 'label' => 'Inspirationen verwalten', 'desc' => 'Erstellung und Verwaltung von Reise-Inspirationen und Empfehlungen für Ihre Kunden.'],
                        'customer.travel_detail_link.media.manage' => ['icon' => 'fa-photo-film', 'label' => 'Medien verwalten', 'desc' => 'Upload und Verwaltung von Bildern, Videos und Dokumenten für Ihre Reisedetail-Links.'],
                        'subscription' => ['icon' => 'fa-credit-card', 'label' => 'Abonnement', 'desc' => 'Verwaltung Ihres Abonnements mit Lizenzdetails, Laufzeit und verfügbaren Erweiterungen.'],
                    ];
                    $hiddenFeatures = ['embed.corona', 'content.infosystem', 'content.cruise_operator'];
                    $trsMenuVisibility = config('trs.menu_items', []);
                @endphp

                @foreach($featureCards as $featureKey => $card)
                    @if(!in_array($featureKey, $hiddenFeatures) && ($trsMenuVisibility[$featureKey] ?? true))
                        @php $isActive = in_array($featureKey, $activeFeatures); @endphp
                        <div class="bg-white rounded-lg border border-gray-200 p-5 mt-5">
                            <div class="flex items-start gap-4">
                                <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                                    <i class="fas {{ $card['icon'] }} text-2xl text-gray-400"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-700">{{ $card['label'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $card['desc'] }}</p>
                                </div>
                                <div class="w-32 flex-shrink-0 flex justify-end">
                                    @if($isActive)
                                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                            <i class="fas fa-check-circle mr-1.5"></i> Aktiv
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                            <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

            @elseif($settingsSection === 'global-travel-monitor')
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Global Travel Monitor</h3>
                @php
                    $pluginClient = $customer->pluginClient;
                    $gtmTab = request()->query('tab', 'general');
                @endphp

                {{-- Tabs --}}
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex gap-6" aria-label="Tabs">
                            <a href="{{ route('customer.settings', ['section' => 'global-travel-monitor', 'tab' => 'general']) }}"
                               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $gtmTab === 'general' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <i class="fas fa-cog mr-1.5"></i>Allgemeines
                            </a>
                            <a href="{{ route('customer.settings', ['section' => 'global-travel-monitor', 'tab' => 'notifications']) }}"
                               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $gtmTab === 'notifications' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <i class="fas fa-bell mr-1.5"></i>Benachrichtigungen
                            </a>
                        </nav>
                    </div>
                </div>

                @if($gtmTab === 'general')

                @if(!$pluginClient)
                    <div class="bg-white rounded-lg border border-gray-200 p-5" x-data="{ showOnboarding: false, onboardingLoading: false, integrationType: 'website' }">
                        <div class="flex items-start gap-4">
                            <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                                <i class="fas fa-puzzle-piece text-2xl text-gray-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-700">Plugin</p>
                                <p class="text-xs text-gray-500 mt-1">Mit dem Global Travel Monitor Plugin binden Sie eine interaktive Weltkarte mit aktuellen Sicherheitsereignissen direkt in Ihre Website oder App ein. Nach der Einrichtung können Sie hier erlaubte Domains verwalten, Embed-Codes kopieren und die Nutzungsstatistik einsehen.</p>
                            </div>
                            <div class="w-32 flex-shrink-0 flex justify-end">
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                    <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 ml-12 mr-36">
                            <button @click="showOnboarding = true" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                                <i class="fas fa-arrow-right mr-2"></i> Einrichten
                            </button>
                        </div>

                        {{-- Onboarding Modal --}}
                        <div x-show="showOnboarding" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showOnboarding = false">
                            <div class="fixed inset-0 bg-black/50" @click="showOnboarding = false"></div>
                            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-lg font-semibold text-gray-900">Plugin einrichten</h3>
                                    <button @click="showOnboarding = false" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-xmark text-lg"></i>
                                    </button>
                                </div>

                                <form action="{{ route('plugin.onboarding.store') }}" method="POST" @submit="onboardingLoading = true">
                                    @csrf
                                    <input type="hidden" name="_redirect" value="{{ route('customer.settings', ['section' => 'global-travel-monitor']) }}">
                                    <input type="hidden" name="integration_type" :value="integrationType">

                                    <div class="space-y-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Firmenname</label>
                                            <input type="text" name="company_name" value="{{ old('company_name', $customer->company ?? '') }}" required
                                                   placeholder="Meine Firma GmbH"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error('company_name')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Ansprechpartner</label>
                                            <input type="text" name="contact_name" value="{{ old('contact_name', $customer->name ?? '') }}" required
                                                   placeholder="Max Mustermann"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error('contact_name')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Integrationstyp --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Wo soll das Plugin eingebunden werden?</label>
                                            <div class="grid grid-cols-1 gap-2">
                                                {{-- Website --}}
                                                <label class="relative flex items-start gap-3 p-3 border rounded-lg cursor-pointer transition-all"
                                                       :class="integrationType === 'website' || integrationType === 'both' ? 'border-blue-300 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                                    <input type="radio" name="_integration_choice" value="website" x-model="integrationType" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900"><i class="fas fa-globe mr-1.5 text-blue-500"></i>Website</p>
                                                        <p class="text-xs text-gray-500 mt-0.5">Einbindung per iFrame auf Ihrer Website. Hierfür muss die Domain hinterlegt werden, damit nur autorisierte Websites das Plugin laden können.</p>
                                                    </div>
                                                </label>

                                                {{-- App --}}
                                                <label class="relative flex items-start gap-3 p-3 border rounded-lg cursor-pointer transition-all"
                                                       :class="integrationType === 'app' ? 'border-blue-300 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                                    <input type="radio" name="_integration_choice" value="app" x-model="integrationType" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900"><i class="fas fa-mobile-screen mr-1.5 text-purple-500"></i>App (Desktop / Mobile)</p>
                                                        <p class="text-xs text-gray-500 mt-0.5">Einbindung per WebView in Ihrer nativen App (Android, iOS, Electron, etc.). Keine Domain nötig &ndash; der Zugriff wird direkt per API-Key autorisiert.</p>
                                                    </div>
                                                </label>

                                                {{-- Beides --}}
                                                <label class="relative flex items-start gap-3 p-3 border rounded-lg cursor-pointer transition-all"
                                                       :class="integrationType === 'both' ? 'border-blue-300 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                                    <input type="radio" name="_integration_choice" value="both" x-model="integrationType" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900"><i class="fas fa-layer-group mr-1.5 text-green-500"></i>Beides</p>
                                                        <p class="text-xs text-gray-500 mt-0.5">Sie möchten das Plugin sowohl auf einer Website als auch in einer App nutzen. Domain-Validierung und App-Zugang werden aktiviert.</p>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        {{-- Domain (nur bei Website oder Beides) --}}
                                        <div x-show="integrationType === 'website' || integrationType === 'both'" x-transition>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Ihre Domain</label>
                                            <input type="text" name="domain" value="{{ old('domain') }}"
                                                   :required="integrationType === 'website' || integrationType === 'both'"
                                                   placeholder="beispiel.de"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                            <p class="mt-1 text-xs text-gray-500">Die Domain, auf der das Plugin eingebunden wird (ohne https://). Weitere Domains können Sie nach der Einrichtung hinzufügen.</p>
                                            @error('domain')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-3 mt-6">
                                        <button type="button" @click="showOnboarding = false"
                                                class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                            Abbrechen
                                        </button>
                                        <button type="submit" :disabled="onboardingLoading"
                                                :class="onboardingLoading ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                                                class="px-4 py-2 text-sm text-white rounded-lg flex items-center gap-2">
                                            <i class="fas" :class="onboardingLoading ? 'fa-spinner fa-spin' : 'fa-lock'"></i>
                                            <span x-text="onboardingLoading ? 'Wird eingerichtet...' : 'Plugin einrichten'"></span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    @php
                        $activeKey = $pluginClient->activeKey;
                        $domains = $pluginClient->domains;
                        $apiKey = $activeKey?->public_key ?? 'YOUR_API_KEY';
                        $baseUrl = config('app.url');

                        // Usage stats
                        $thirtyDaysAgo = now()->subDays(30);
                        $pluginStats = [
                            'daily' => \App\Models\PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
                                ->where('created_at', '>=', $thirtyDaysAgo)
                                ->where('event_type', 'embed_view')
                                ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('COUNT(*) as count'))
                                ->groupBy('date')->orderBy('date')->get()->pluck('count', 'date')->toArray(),
                            'total' => \App\Models\PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
                                ->where('created_at', '>=', $thirtyDaysAgo)->count(),
                            'by_type' => \App\Models\PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
                                ->where('created_at', '>=', $thirtyDaysAgo)
                                ->select('event_type', \DB::raw('COUNT(*) as count'))
                                ->groupBy('event_type')->pluck('count', 'event_type')->toArray(),
                            'top_domains' => \App\Models\PluginUsageEvent::where('plugin_client_id', $pluginClient->id)
                                ->where('created_at', '>=', $thirtyDaysAgo)
                                ->select('domain', \DB::raw('COUNT(*) as count'))
                                ->groupBy('domain')->orderByDesc('count')->limit(5)->pluck('count', 'domain')->toArray(),
                        ];
                    @endphp

                    {{-- Erlaubte Domains --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <h4 class="text-sm font-semibold text-gray-900 mb-1">Erlaubte Domains</h4>
                        <p class="text-xs text-gray-500 mb-4">Ohne https:// oder http:// angeben (z.B. meine-website.de)</p>

                        <ul class="divide-y divide-gray-200 mb-4">
                            @forelse($domains as $domain)
                                <li class="py-3 flex justify-between items-center">
                                    <span class="text-sm text-gray-900">{{ $domain->domain }}</span>
                                    @if($domains->count() > 1)
                                        <form action="{{ route('plugin.remove-domain', $domain->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Domain wirklich entfernen?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs">
                                                Entfernen
                                            </button>
                                        </form>
                                    @endif
                                </li>
                            @empty
                                <li class="py-3 text-gray-500 text-sm">Keine Domains konfiguriert.</li>
                            @endforelse
                        </ul>

                        <form action="{{ route('plugin.add-domain') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="text" name="domain" placeholder="neue-domain.de" required
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                                Hinzufügen
                            </button>
                        </form>
                        @error('domain')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- App-Integration --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">App-Integration</h4>
                                <p class="text-xs text-gray-500 mt-1">Ermöglicht die Nutzung in Desktop- und Mobile-Apps (WebView)</p>
                            </div>
                            <form action="{{ route('plugin.toggle-app-access') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-3 py-1.5 border text-xs font-medium rounded-lg {{ $pluginClient->allow_app_access ? 'border-red-300 text-red-700 bg-red-50 hover:bg-red-100' : 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100' }}">
                                    @if($pluginClient->allow_app_access)
                                        <i class="fas fa-xmark mr-1"></i> Deaktivieren
                                    @else
                                        <i class="fas fa-check mr-1"></i> Aktivieren
                                    @endif
                                </button>
                            </form>
                        </div>

                        @if($pluginClient->allow_app_access)
                            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                                <p class="text-xs font-medium text-blue-900 mb-1">Integration in Ihre App</p>
                                <p class="text-xs text-blue-700 mb-2">Laden Sie folgende URL in einem WebView:</p>
                                <div class="p-2 bg-white rounded border border-blue-200 overflow-x-auto">
                                    <code class="text-xs text-gray-800 font-mono break-all">{{ $baseUrl }}/embed/dashboard?key={{ $apiKey }}</code>
                                </div>
                                <p class="text-xs text-blue-600 mt-2">Funktioniert mit: Android WebView, iOS WKWebView, Electron, Qt WebEngine, etc.</p>
                            </div>
                        @else
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-600">Aktivieren Sie den App-Zugang, um das Plugin ohne Domain-Validierung in Desktop- oder Mobile-Apps nutzen zu können.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Einbindung --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <h4 class="text-sm font-semibold text-gray-900 mb-1">Einbindung</h4>
                        <p class="text-xs text-gray-500 mb-4">Wählen Sie eine der drei Optionen und kopieren Sie den Code in Ihre Website.</p>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            {{-- Option 1: Ereignisliste --}}
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-blue-50 px-3 py-2 border-b border-blue-100">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">Option 1</span>
                                        <span class="text-xs font-medium text-gray-900">Ereignisliste</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Für schmale Spalten (300-400px)</p>
                                </div>
                                <div class="p-3">
                                    <div class="bg-gray-900 rounded-lg p-2 overflow-x-auto mb-2">
                                        <pre class="text-[10px] text-green-400 font-mono whitespace-pre-wrap" id="gtm-code-events">&lt;iframe
  src="{{ $baseUrl }}/embed/events?key={{ $apiKey }}"
  width="400" height="600"
  frameborder="0"&gt;
&lt;/iframe&gt;</pre>
                                    </div>
                                    <button onclick="copyGtmCode('events')" class="w-full px-3 py-1.5 border border-gray-300 text-xs rounded-lg text-gray-700 bg-white hover:bg-gray-50 flex items-center justify-center gap-1">
                                        <i class="fas fa-copy"></i> Code kopieren
                                    </button>
                                </div>
                            </div>

                            {{-- Option 2: Kartenansicht --}}
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-green-50 px-3 py-2 border-b border-green-100">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700">Option 2</span>
                                        <span class="text-xs font-medium text-gray-900">Kartenansicht</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Interaktive Weltkarte</p>
                                </div>
                                <div class="p-3">
                                    <div class="bg-gray-900 rounded-lg p-2 overflow-x-auto mb-2">
                                        <pre class="text-[10px] text-green-400 font-mono whitespace-pre-wrap" id="gtm-code-map">&lt;iframe
  src="{{ $baseUrl }}/embed/map?key={{ $apiKey }}"
  width="100%" height="600"
  frameborder="0"&gt;
&lt;/iframe&gt;</pre>
                                    </div>
                                    <button onclick="copyGtmCode('map')" class="w-full px-3 py-1.5 border border-gray-300 text-xs rounded-lg text-gray-700 bg-white hover:bg-gray-50 flex items-center justify-center gap-1">
                                        <i class="fas fa-copy"></i> Code kopieren
                                    </button>
                                </div>
                            </div>

                            {{-- Option 3: Komplettansicht --}}
                            <div class="border border-gray-200 rounded-lg overflow-hidden relative">
                                <div class="absolute top-1.5 right-1.5 z-10">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-500 text-white">
                                        <i class="fas fa-thumbs-up text-[8px]"></i> Empfohlen
                                    </span>
                                </div>
                                <div class="bg-purple-50 px-3 py-2 border-b border-purple-100">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-700">Option 3</span>
                                        <span class="text-xs font-medium text-gray-900">Komplettansicht</span>
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Liste + Karte kombiniert</p>
                                </div>
                                <div class="p-3">
                                    <div class="bg-gray-900 rounded-lg p-2 overflow-x-auto mb-2">
                                        <pre class="text-[10px] text-green-400 font-mono whitespace-pre-wrap" id="gtm-code-dashboard">&lt;iframe
  src="{{ $baseUrl }}/embed/dashboard?key={{ $apiKey }}"
  width="100%" height="800"
  frameborder="0"&gt;
&lt;/iframe&gt;</pre>
                                    </div>
                                    <button onclick="copyGtmCode('dashboard')" class="w-full px-3 py-1.5 border border-gray-300 text-xs rounded-lg text-gray-700 bg-white hover:bg-gray-50 flex items-center justify-center gap-1">
                                        <i class="fas fa-copy"></i> Code kopieren
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 p-2 bg-gray-50 rounded-lg">
                            <p class="text-[10px] text-gray-500">
                                <strong>Tipp:</strong> Weitere Parameter wie <code class="bg-gray-200 px-1 rounded">timePeriod</code>, <code class="bg-gray-200 px-1 rounded">priorities</code> oder <code class="bg-gray-200 px-1 rounded">continents</code> finden Sie in der <a href="{{ url('/doc-plugin') }}" class="text-blue-600 hover:underline" target="_blank">Dokumentation</a>.
                            </p>
                        </div>
                    </div>

                    {{-- Nutzungsstatistik --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Nutzungsstatistik (letzte 30 Tage)</h4>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                            <div class="bg-blue-50 rounded-lg p-3">
                                <p class="text-xs text-blue-600 font-medium">Gesamt-Aufrufe</p>
                                <p class="text-xl font-bold text-blue-900">{{ number_format($pluginStats['total']) }}</p>
                            </div>
                            @foreach($pluginStats['by_type'] as $type => $count)
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-600 font-medium">{{ ucfirst(str_replace('_', ' ', $type)) }}</p>
                                    <p class="text-xl font-bold text-gray-900">{{ number_format($count) }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if(count($pluginStats['top_domains']) > 0)
                            <h5 class="text-xs font-medium text-gray-900 mb-2">Top Domains</h5>
                            <ul class="space-y-1 mb-4">
                                @foreach($pluginStats['top_domains'] as $domain => $count)
                                    <li class="flex justify-between items-center text-xs">
                                        <span class="text-gray-700">{{ $domain }}</span>
                                        <span class="text-gray-500">{{ number_format($count) }} Aufrufe</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if(count($pluginStats['daily']) > 0)
                            <h5 class="text-xs font-medium text-gray-900 mt-4 mb-3">Tägliche Aufrufe</h5>
                            @php
                                $maxCount = max($pluginStats['daily']) ?: 1;
                                $dailyData = $pluginStats['daily'];
                            @endphp
                            <div class="relative">
                                <div class="absolute left-0 top-0 bottom-6 w-8 flex flex-col justify-between text-[10px] text-gray-400">
                                    <span>{{ number_format($maxCount) }}</span>
                                    <span>{{ number_format($maxCount / 2) }}</span>
                                    <span>0</span>
                                </div>
                                <div class="ml-10">
                                    <div class="relative h-32 border-b border-l border-gray-200">
                                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                                            <div class="border-t border-gray-100 border-dashed"></div>
                                            <div class="border-t border-gray-100 border-dashed"></div>
                                            <div></div>
                                        </div>
                                        <div class="absolute inset-0 flex items-end gap-px px-1">
                                            @foreach($dailyData as $date => $count)
                                                @php
                                                    $height = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                                                @endphp
                                                <div class="flex-1 group relative flex flex-col items-center justify-end h-full">
                                                    <div class="w-full bg-blue-500 hover:bg-blue-600 rounded-t transition-colors cursor-pointer"
                                                         style="height: {{ max($height, 2) }}%; min-height: 2px;"></div>
                                                    <div class="absolute bottom-full mb-2 hidden group-hover:block z-10">
                                                        <div class="bg-gray-900 text-white text-[10px] rounded py-1 px-2 whitespace-nowrap">
                                                            {{ \Carbon\Carbon::parse($date)->format('d.m.') }}: {{ number_format($count) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex justify-between mt-1 text-[10px] text-gray-400">
                                        @php
                                            $dates = array_keys($dailyData);
                                            $firstDate = \Carbon\Carbon::parse(reset($dates))->format('d.m.');
                                            $lastDate = \Carbon\Carbon::parse(end($dates))->format('d.m.');
                                        @endphp
                                        <span>{{ $firstDate }}</span>
                                        <span>{{ $lastDate }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- API-Zugang (immer sichtbar, unabhängig vom Plugin-Status) --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mt-5" x-data="apiTokenManager()">
                    <div class="flex items-start gap-4">
                        <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-code text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">API-Zugang</p>
                            <p class="text-xs text-gray-500 mt-1">Über die API können Sie Sicherheitsereignisse, Länderinformationen und Reisewarnungen programmatisch in Ihre eigenen Systeme integrieren &ndash; z.&thinsp;B. in ein Intranet, ein Travel-Management-System oder automatisierte Workflows. Zur Authentifizierung wird ein API-Token benötigt.</p>
                        </div>
                        <div class="w-32 flex-shrink-0 flex justify-end">
                            <span x-show="hasToken" x-cloak class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-check-circle mr-1.5"></i> Aktiv
                            </span>
                            <button x-show="!hasToken" x-cloak @click="generateToken" :disabled="loading"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 whitespace-nowrap">
                                <i class="fas mr-1.5" :class="loading ? 'fa-spinner fa-spin' : 'fa-key'"></i>
                                <span x-text="loading ? 'Wird generiert...' : 'Aktivieren'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Token anzeigen --}}
                    <div x-show="generatedToken" x-cloak class="mt-4 ml-12 mr-36 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-xs text-green-800 font-medium mb-2"><i class="fas fa-check-circle mr-1"></i> API Token erfolgreich generiert</p>
                        <div class="flex gap-2 items-center">
                            <input type="text" x-model="generatedToken" readonly class="flex-1 px-3 py-2 bg-white border border-green-300 rounded-lg text-xs font-mono select-all" @click="$el.select()">
                            <button @click="copyToken" class="px-3 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 flex items-center gap-1">
                                <i class="fas fa-copy"></i> <span x-text="copied ? 'Kopiert!' : 'Kopieren'"></span>
                            </button>
                        </div>
                        <p class="text-[10px] text-green-700 mt-2"><i class="fas fa-info-circle mr-1"></i>Bitte speichern Sie diesen Token sicher. Er wird nur einmal angezeigt.</p>
                    </div>

                    {{-- Aktionen wenn Token vorhanden --}}
                    <div x-show="hasToken && !generatedToken" x-cloak class="mt-4 ml-12 mr-36 flex gap-3">
                        <button @click="generateToken" :disabled="loading"
                            :class="loading ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                            class="px-4 py-2 text-white text-xs rounded-lg flex items-center gap-1">
                            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-rotate'"></i>
                            <span x-text="loading ? 'Wird generiert...' : 'Neuen Token generieren'"></span>
                        </button>
                        <button @click="revokeToken" :disabled="loading"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg flex items-center gap-1">
                            <i class="fas fa-trash"></i> Token widerrufen
                        </button>
                    </div>
                </div>

                @elseif($gtmTab === 'notifications')
                {{-- Benachrichtigungen Tab --}}
                <p class="text-sm text-gray-500 mb-5"><b>Bleiben Sie automatisch über für Sie relevante Ereignisse informiert.</b><br>Erstellen Sie Regeln basierend auf Kriterien wie Land, Risikostufe oder Ereignistyp und erhalten Sie passende Benachrichtigungen per E-Mail.</p>
                    @if(auth('customer')->user()->isFeatureEnabled('navigation_risk_overview_enabled'))
                    @php
                        $notifCustomer = auth('customer')->user();
                        $notifTemplateCount = \App\Models\NotificationTemplate::forCustomer($notifCustomer->id)->count();
                        $notifCustomTemplateCount = $notifCustomer->notificationTemplates()->count();
                        $notifSystemTemplateCount = \App\Models\NotificationTemplate::system()->count();
                    @endphp

                    {{-- Globale Einstellungen --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Globale Einstellungen</h4>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-700">Automatische Benachrichtigungen</p>
                                <p class="text-[10px] text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Wenn aktiviert, werden E-Mails basierend auf Ihren Regeln versendet.</p>
                            </div>
                            <form method="POST" action="{{ route('customer.notification-settings.toggle') }}">
                                @csrf
                                <button type="submit" class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-11 h-6 {{ $notifCustomer->notifications_enabled ? 'bg-blue-600' : 'bg-gray-200' }} rounded-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all {{ $notifCustomer->notifications_enabled ? 'after:translate-x-full after:border-white' : '' }}"></div>
                                </button>
                            </form>
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $notifCustomer->notifications_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                <i class="fas {{ $notifCustomer->notifications_enabled ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                {{ $notifCustomer->notifications_enabled ? 'Aktiviert' : 'Deaktiviert' }}
                            </span>
                        </div>
                    </div>

                    {{-- E-Mail-Vorlagen --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="{
                        showTemplateModal: false,
                        editTemplateId: null,
                        templates: [],
                        loading: true,
                        async init() { await this.loadTemplates(); },
                        async loadTemplates() {
                            this.loading = true;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.templates.index') }}?source=global-travel-monitor', { headers: { 'Accept': 'application/json' } });
                                if (r.ok) {
                                    const d = await r.json();
                                    this.templates = d.templates || d;
                                }
                            } catch(e) {}
                            this.loading = false;
                        },
                        openCreate() {
                            this.editTemplateId = null;
                            this.showTemplateModal = true;
                            Livewire.dispatch('load-template', { id: null });
                        },
                        openEdit(id) {
                            this.editTemplateId = id;
                            this.showTemplateModal = true;
                            Livewire.dispatch('load-template', { id: id });
                        },
                        async sendTestMail(id) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Test-Mail wird versendet...', type: 'info' } }));
                            try {
                                const r = await fetch('/customer/notification-settings/templates/' + id + '/test', {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                const d = await r.json();
                                if (d.success) {
                                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message, type: 'success' } }));
                                    window.dispatchEvent(new CustomEvent('reload-logs'));
                                } else {
                                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message || 'Fehler beim Versenden.', type: 'error' } }));
                                }
                            } catch(e) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Fehler beim Versenden.', type: 'error' } })); }
                        },
                        async deleteTemplate(id) {
                            if (!confirm('Möchten Sie diese Vorlage wirklich löschen?')) return;
                            try {
                                const r = await fetch('/customer/notification-settings/templates/' + id, {
                                    method: 'DELETE',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                if (r.ok) this.loadTemplates();
                            } catch(e) {}
                        },
                    }"
                    x-on:template-saved.window="showTemplateModal = false; loadTemplates()"
                    x-on:template-deleted.window="showTemplateModal = false; loadTemplates()">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-file-alt mr-2 text-blue-500"></i>E-Mail-Vorlagen</h4>
                            <button @click="openCreate()" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Neue E-Mail-Vorlage
                            </button>
                        </div>

                        <div x-show="loading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!loading && templates.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-file-alt text-2xl mb-2"></i>
                            <p class="text-xs">Keine Vorlagen vorhanden.</p>
                        </div>

                        <div x-show="!loading && templates.length > 0" class="space-y-2">
                            <template x-for="tpl in templates" :key="tpl.id">
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-medium text-gray-900" x-text="tpl.name"></span>
                                                <span x-show="tpl.is_system" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800">
                                                    <i class="fas fa-lock mr-0.5 text-[8px]"></i> System
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-gray-500"><i class="fas fa-envelope mr-1"></i>Betreff: <span x-text="tpl.subject"></span></p>
                                        </div>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-ellipsis-vertical text-sm"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition x-cloak
                                                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                                <button x-show="!tpl.is_system" @click="openEdit(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                                </button>
                                                <button @click="sendTestMail(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-paper-plane w-4 text-center text-amber-500"></i> Test-Mail versenden
                                                </button>
                                                <div x-show="!tpl.is_system" class="border-t border-gray-100 my-1"></div>
                                                <button x-show="!tpl.is_system" @click="deleteTemplate(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fas fa-trash w-4 text-center"></i> Löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Template Modal --}}
                        <div x-show="showTemplateModal" x-cloak class="fixed z-[10000] flex items-center justify-center" style="top: 64px; bottom: 56px; left: 0; right: 0; padding: 8px;" @keydown.escape.window="showTemplateModal = false">
                            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showTemplateModal = false"></div>
                            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 flex flex-col" style="max-height: 100%;">
                                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-gray-900" x-text="editTemplateId ? 'E-Mail-Vorlage bearbeiten' : 'Neue E-Mail-Vorlage'"></h4>
                                    <button @click="showTemplateModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-lg"></i></button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-6">
                                    @livewire('customer.notification-template-form', ['source' => 'global-travel-monitor'], key('gtm-tpl-form'))
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Regeln --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="{
                        rules: [], rulesLoading: true,
                        async init() { await this.loadRules(); },
                        async loadRules() {
                            this.rulesLoading = true;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.rules.json') }}?source=global-travel-monitor', { headers: { 'Accept': 'application/json' } });
                                if (r.ok) { const d = await r.json(); this.rules = d.rules || []; }
                            } catch(e) {}
                            this.rulesLoading = false;
                        },
                        async sendRuleTestMail(id) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Test-Mail wird versendet...', type: 'info' } }));
                            try {
                                const r = await fetch('/customer/notification-settings/rules/' + id + '/test', {
                                    method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                const d = await r.json();
                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message, type: d.success ? 'success' : 'error' } }));
                                if (d.success) window.dispatchEvent(new CustomEvent('reload-logs'));
                            } catch(e) {
                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Fehler beim Versenden.', type: 'error' } }));
                            }
                        },
                        async deleteRule(id) {
                            if (!confirm('Möchten Sie diese Regel wirklich löschen?')) return;
                            try {
                                const r = await fetch('/customer/notification-settings/rules/' + id, {
                                    method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                if (r.ok) this.loadRules();
                            } catch(e) {}
                        },
                        showRuleModal: false,
                        editRuleId: null,
                        openCreateRule() { this.editRuleId = null; this.showRuleModal = true; Livewire.dispatch('load-rule', { id: null }); },
                        openEditRule(id) { this.editRuleId = id; this.showRuleModal = true; Livewire.dispatch('load-rule', { id: id }); },
                    }" x-on:reload-rules.window="loadRules()" x-on:rule-saved.window="showRuleModal = false; loadRules()" x-on:rule-deleted.window="showRuleModal = false; loadRules()">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-list-check mr-2 text-blue-500"></i>Benachrichtigungs-Regeln</h4>
                            <button @click="openCreateRule()" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Neue Regel
                            </button>
                        </div>

                        <div x-show="rulesLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!rulesLoading && rules.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p class="text-xs">Noch keine Regeln erstellt.</p>
                        </div>

                        <div x-show="!rulesLoading && rules.length > 0" class="space-y-2">
                            <template x-for="rule in rules" :key="rule.id">
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-medium text-gray-900" x-text="rule.name"></span>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                                      :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                                      x-text="rule.is_active ? 'aktiv' : 'inaktiv'"></span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-gray-500">
                                                <span x-show="rule.risk_level_labels.length"><i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i><span x-text="rule.risk_level_labels.join(', ')"></span></span>
                                                <span x-show="rule.category_labels.length"><i class="fas fa-tag text-blue-500 mr-1"></i><span x-text="rule.category_labels.join(', ')"></span></span>
                                                <span><i class="fas fa-globe text-green-500 mr-1"></i><span x-text="rule.country_count ? rule.country_count + ' Länder' : 'Alle Länder'"></span></span>
                                                <span><i class="fas fa-envelope text-purple-500 mr-1"></i><span x-text="rule.recipients_count"></span> Empfänger</span>
                                            </div>
                                        </div>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-ellipsis-vertical text-sm"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition x-cloak
                                                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                                <button @click="openEditRule(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                                </button>
                                                <button @click="sendRuleTestMail(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-paper-plane w-4 text-center text-amber-500"></i> Test-Mail versenden
                                                </button>
                                                <div class="border-t border-gray-100 my-1"></div>
                                                <button @click="deleteRule(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fas fa-trash w-4 text-center"></i> Löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Rule Modal --}}
                        <div x-show="showRuleModal" x-cloak class="fixed z-[10000] flex items-center justify-center" style="top: 64px; bottom: 56px; left: 0; right: 0; padding: 8px;" @keydown.escape.window="if(showRuleModal) showRuleModal = false">
                            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showRuleModal = false"></div>
                            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 flex flex-col" style="max-height: 100%;">
                                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-gray-900" x-text="editRuleId ? 'Regel bearbeiten' : 'Neue Benachrichtigungs-Regel'"></h4>
                                    <button @click="showRuleModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-lg"></i></button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-6">
                                    @livewire('customer.notification-rule-form', ['source' => 'global-travel-monitor'], key('gtm-rule-form'))
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Versandprotokoll --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5" x-on:reload-logs.window="loadLogs(1)" x-data="{
                        logs: [], logsMeta: {}, logsLoading: true, logsPage: 1,
                        async init() { await this.loadLogs(); },
                        async loadLogs(page) {
                            this.logsLoading = true;
                            if (page) this.logsPage = page;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.logs') }}?source=global-travel-monitor&page=' + this.logsPage, { headers: { 'Accept': 'application/json' } });
                                if (r.ok) {
                                    const d = await r.json();
                                    this.logs = d.data || [];
                                    this.logsMeta = { current_page: d.current_page, last_page: d.last_page, total: d.total, from: d.from, to: d.to };
                                }
                            } catch(e) {}
                            this.logsLoading = false;
                        }
                    }">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-history mr-2 text-blue-500"></i>Versandprotokoll</h4>
                            <span class="text-[10px] text-gray-400" x-show="logsMeta.total" x-text="logsMeta.total + ' Einträge'"></span>
                        </div>

                        <div x-show="logsLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!logsLoading && logs.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p class="text-xs">Noch keine Nachrichten versendet.</p>
                        </div>

                        <div x-show="!logsLoading && logs.length > 0">
                            <div class="space-y-2 mb-4">
                                <template x-for="log in logs" :key="log.id">
                                    <div class="border rounded-lg p-3 text-xs" :class="log.status === 'sent' ? 'border-gray-200' : 'border-red-200 bg-red-50'">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <i class="fas text-[10px]" :class="log.status === 'sent' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'"></i>
                                                    <span class="font-medium text-gray-900 truncate" x-text="log.subject"></span>
                                                    <span x-show="log.is_test" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-800">Test</span>
                                                </div>
                                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-gray-500">
                                                    <span><i class="fas fa-envelope mr-1"></i><span x-text="log.recipient_email"></span></span>
                                                    <span x-show="log.template_name"><i class="fas fa-file-alt mr-1"></i>Vorlage: <span x-text="log.template_name"></span></span>
                                                    <span x-show="log.rule_name"><i class="fas fa-list-check mr-1"></i>Regel: <span x-text="log.rule_name"></span></span>
                                                    <span x-show="log.notification_rule && !log.rule_name"><i class="fas fa-list-check mr-1"></i>Regel: <span x-text="log.notification_rule?.name"></span></span>
                                                </div>
                                                <p x-show="log.error_message" class="text-[10px] text-red-600 mt-1" x-text="log.error_message"></p>
                                            </div>
                                            <div class="text-[10px] text-gray-400 flex-shrink-0 text-right">
                                                <div x-text="new Date(log.created_at).toLocaleDateString('de-DE')"></div>
                                                <div x-text="new Date(log.created_at).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'})"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Paginator --}}
                            <div x-show="logsMeta.last_page > 1" class="flex items-center justify-between">
                                <p class="text-[10px] text-gray-500" x-text="'Seite ' + logsMeta.current_page + ' von ' + logsMeta.last_page"></p>
                                <div class="flex gap-1">
                                    <button @click="loadLogs(logsPage - 1)" :disabled="logsPage <= 1"
                                        :class="logsPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-2 py-1 text-[10px] rounded border border-gray-200">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <template x-for="p in logsMeta.last_page" :key="p">
                                        <button @click="loadLogs(p)"
                                            class="px-2 py-1 text-[10px] rounded border"
                                            :class="p === logsMeta.current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                                            x-text="p" x-show="Math.abs(p - logsMeta.current_page) < 3 || p === 1 || p === logsMeta.last_page">
                                        </button>
                                    </template>
                                    <button @click="loadLogs(logsPage + 1)" :disabled="logsPage >= logsMeta.last_page"
                                        :class="logsPage >= logsMeta.last_page ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-2 py-1 text-[10px] rounded border border-gray-200">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Globale Toast Notification --}}
                    <div x-data="{ msg: '', type: 'success', visible: false }"
                         x-on:show-toast.window="msg = $event.detail.message; type = $event.detail.type; visible = true; if(type !== 'info') setTimeout(() => visible = false, 5000)">
                        <div x-show="visible" x-cloak x-transition
                             class="fixed top-20 right-6 max-w-sm z-[10001] rounded-lg shadow-lg border px-4 py-3 flex items-start gap-3"
                             :class="{ 'bg-green-50 border-green-200': type==='success', 'bg-red-50 border-red-200': type==='error', 'bg-blue-50 border-blue-200': type==='info' }">
                            <i class="fas mt-0.5" :class="{ 'fa-check-circle text-green-500': type==='success', 'fa-exclamation-circle text-red-500': type==='error', 'fa-spinner fa-spin text-blue-500': type==='info' }"></i>
                            <p class="flex-1 text-xs font-medium" :class="{ 'text-green-800': type==='success', 'text-red-800': type==='error', 'text-blue-800': type==='info' }" x-text="msg"></p>
                            <button @click="visible = false" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    @else
                    <div class="bg-white rounded-lg border border-gray-200 p-5 text-center">
                        <i class="fas fa-shield-exclamation text-3xl text-gray-300 mb-2"></i>
                        <p class="text-xs text-gray-500">TravelAlert ist nicht aktiviert.</p>
                    </div>
                    @endif
                @endif

            @elseif($settingsSection === 'travel-alert')
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Travel Alert</h3>

                @php
                    $taTab = request()->query('tab', 'general');
                @endphp

                {{-- Tabs --}}
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex gap-6" aria-label="Tabs">
                            <a href="{{ route('customer.settings', ['section' => 'travel-alert', 'tab' => 'general']) }}"
                               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $taTab === 'general' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <i class="fas fa-cog mr-1.5"></i>Allgemeines
                            </a>
                            <a href="{{ route('customer.settings', ['section' => 'travel-alert', 'tab' => 'notifications']) }}"
                               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $taTab === 'notifications' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <i class="fas fa-bell mr-1.5"></i>Benachrichtigungen
                            </a>
                        </nav>
                    </div>
                </div>

                @if($taTab === 'general')
                <div class="bg-white rounded-lg border border-gray-200 p-5">
                    <div class="flex items-center gap-3 text-gray-400">
                        <i class="fas fa-triangle-exclamation text-2xl"></i>
                        <p class="text-sm">Dieser Bereich wird derzeit eingerichtet.</p>
                    </div>
                </div>

                @elseif($taTab === 'notifications')
                {{-- Benachrichtigungen Tab --}}
                <p class="text-sm text-gray-500 mb-5"><b>Erstellen Sie Benachrichtigungsregeln, um informiert zu werden, wenn Ereignisse Ihre Reisen betreffen.</b><br>Sie erhalten eine E-Mail, sobald ein Ereignis Auswirkungen auf Reisen in Ihrem Portfolio hat.</p>
                    @if(auth('customer')->user()->isFeatureEnabled('navigation_risk_overview_enabled'))
                    @php
                        $taNotifCustomer = auth('customer')->user();
                    @endphp

                    {{-- Globale Einstellungen --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Globale Einstellungen</h4>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-700">Automatische Benachrichtigungen</p>
                                <p class="text-[10px] text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Wenn aktiviert, werden E-Mails basierend auf Ihren Regeln versendet.</p>
                            </div>
                            <form method="POST" action="{{ route('customer.notification-settings.toggle') }}">
                                @csrf
                                <button type="submit" class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-11 h-6 {{ $taNotifCustomer->notifications_enabled ? 'bg-blue-600' : 'bg-gray-200' }} rounded-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all {{ $taNotifCustomer->notifications_enabled ? 'after:translate-x-full after:border-white' : '' }}"></div>
                                </button>
                            </form>
                        </div>
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $taNotifCustomer->notifications_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                <i class="fas {{ $taNotifCustomer->notifications_enabled ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                {{ $taNotifCustomer->notifications_enabled ? 'Aktiviert' : 'Deaktiviert' }}
                            </span>
                        </div>
                    </div>

                    {{-- E-Mail-Vorlagen --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="{
                        showTemplateModal: false,
                        editTemplateId: null,
                        templates: [],
                        loading: true,
                        async init() { await this.loadTemplates(); },
                        async loadTemplates() {
                            this.loading = true;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.templates.index') }}?source=travel-alert', { headers: { 'Accept': 'application/json' } });
                                if (r.ok) {
                                    const d = await r.json();
                                    this.templates = d.templates || d;
                                }
                            } catch(e) {}
                            this.loading = false;
                        },
                        openCreate() {
                            this.editTemplateId = null;
                            this.showTemplateModal = true;
                            Livewire.dispatch('load-template', { id: null });
                        },
                        openEdit(id) {
                            this.editTemplateId = id;
                            this.showTemplateModal = true;
                            Livewire.dispatch('load-template', { id: id });
                        },
                        async sendTestMail(id) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Test-Mail wird versendet...', type: 'info' } }));
                            try {
                                const r = await fetch('/customer/notification-settings/templates/' + id + '/test', {
                                    method: 'POST',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                const d = await r.json();
                                if (d.success) {
                                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message, type: 'success' } }));
                                    window.dispatchEvent(new CustomEvent('reload-logs'));
                                } else {
                                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message || 'Fehler beim Versenden.', type: 'error' } }));
                                }
                            } catch(e) { window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Fehler beim Versenden.', type: 'error' } })); }
                        },
                        async deleteTemplate(id) {
                            if (!confirm('Möchten Sie diese Vorlage wirklich löschen?')) return;
                            try {
                                const r = await fetch('/customer/notification-settings/templates/' + id, {
                                    method: 'DELETE',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                if (r.ok) this.loadTemplates();
                            } catch(e) {}
                        },
                    }"
                    x-on:template-saved.window="showTemplateModal = false; loadTemplates()"
                    x-on:template-deleted.window="showTemplateModal = false; loadTemplates()">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-file-alt mr-2 text-blue-500"></i>E-Mail-Vorlagen</h4>
                            <button @click="openCreate()" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Neue E-Mail-Vorlage
                            </button>
                        </div>

                        <div x-show="loading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!loading && templates.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-file-alt text-2xl mb-2"></i>
                            <p class="text-xs">Keine Vorlagen vorhanden.</p>
                        </div>

                        <div x-show="!loading && templates.length > 0" class="space-y-2">
                            <template x-for="tpl in templates" :key="tpl.id">
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-medium text-gray-900" x-text="tpl.name"></span>
                                                <span x-show="tpl.is_system" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-800">
                                                    <i class="fas fa-lock mr-0.5 text-[8px]"></i> System
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-gray-500"><i class="fas fa-envelope mr-1"></i>Betreff: <span x-text="tpl.subject"></span></p>
                                        </div>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-ellipsis-vertical text-sm"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition x-cloak
                                                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                                <button x-show="!tpl.is_system" @click="openEdit(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                                </button>
                                                <button @click="sendTestMail(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-paper-plane w-4 text-center text-amber-500"></i> Test-Mail versenden
                                                </button>
                                                <div x-show="!tpl.is_system" class="border-t border-gray-100 my-1"></div>
                                                <button x-show="!tpl.is_system" @click="deleteTemplate(tpl.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fas fa-trash w-4 text-center"></i> Löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Template Modal --}}
                        <div x-show="showTemplateModal" x-cloak class="fixed z-[10000] flex items-center justify-center" style="top: 64px; bottom: 56px; left: 0; right: 0; padding: 8px;" @keydown.escape.window="showTemplateModal = false">
                            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showTemplateModal = false"></div>
                            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 flex flex-col" style="max-height: 100%;">
                                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-gray-900" x-text="editTemplateId ? 'E-Mail-Vorlage bearbeiten' : 'Neue E-Mail-Vorlage'"></h4>
                                    <button @click="showTemplateModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-lg"></i></button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-6">
                                    @livewire('customer.notification-template-form', ['source' => 'travel-alert'], key('ta-tpl-form'))
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Regeln --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="{
                        rules: [], rulesLoading: true,
                        async init() { await this.loadRules(); },
                        async loadRules() {
                            this.rulesLoading = true;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.rules.json') }}?source=travel-alert', { headers: { 'Accept': 'application/json' } });
                                if (r.ok) { const d = await r.json(); this.rules = d.rules || []; }
                            } catch(e) {}
                            this.rulesLoading = false;
                        },
                        async sendRuleTestMail(id) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Test-Mail wird versendet...', type: 'info' } }));
                            try {
                                const r = await fetch('/customer/notification-settings/rules/' + id + '/test', {
                                    method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                const d = await r.json();
                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: d.message, type: d.success ? 'success' : 'error' } }));
                                if (d.success) window.dispatchEvent(new CustomEvent('reload-logs'));
                            } catch(e) {
                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Fehler beim Versenden.', type: 'error' } }));
                            }
                        },
                        async deleteRule(id) {
                            if (!confirm('Möchten Sie diese Regel wirklich löschen?')) return;
                            try {
                                const r = await fetch('/customer/notification-settings/rules/' + id, {
                                    method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                });
                                if (r.ok) this.loadRules();
                            } catch(e) {}
                        },
                        showRuleModal: false,
                        editRuleId: null,
                        openCreateRule() { this.editRuleId = null; this.showRuleModal = true; Livewire.dispatch('load-rule', { id: null }); },
                        openEditRule(id) { this.editRuleId = id; this.showRuleModal = true; Livewire.dispatch('load-rule', { id: id }); },
                    }" x-on:reload-rules.window="loadRules()" x-on:rule-saved.window="showRuleModal = false; loadRules()" x-on:rule-deleted.window="showRuleModal = false; loadRules()">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-list-check mr-2 text-blue-500"></i>Benachrichtigungs-Regeln</h4>
                            <button @click="openCreateRule()" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Neue Regel
                            </button>
                        </div>

                        <div x-show="rulesLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!rulesLoading && rules.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p class="text-xs">Noch keine Regeln erstellt.</p>
                        </div>

                        <div x-show="!rulesLoading && rules.length > 0" class="space-y-2">
                            <template x-for="rule in rules" :key="rule.id">
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-gray-300 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-medium text-gray-900" x-text="rule.name"></span>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                                      :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                                      x-text="rule.is_active ? 'aktiv' : 'inaktiv'"></span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-gray-500">
                                                <span x-show="rule.risk_level_labels.length"><i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i><span x-text="rule.risk_level_labels.join(', ')"></span></span>
                                                <span x-show="rule.category_labels.length"><i class="fas fa-tag text-blue-500 mr-1"></i><span x-text="rule.category_labels.join(', ')"></span></span>
                                                <span><i class="fas fa-globe text-green-500 mr-1"></i>Automatisch (Reisedaten)</span>
                                                <span><i class="fas fa-envelope text-purple-500 mr-1"></i><span x-text="rule.recipients_count"></span> Empfänger</span>
                                            </div>
                                        </div>
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <i class="fas fa-ellipsis-vertical text-sm"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-transition x-cloak
                                                 class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                                <button @click="openEditRule(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                                </button>
                                                <button @click="sendRuleTestMail(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                    <i class="fas fa-paper-plane w-4 text-center text-amber-500"></i> Test-Mail versenden
                                                </button>
                                                <div class="border-t border-gray-100 my-1"></div>
                                                <button @click="deleteRule(rule.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i class="fas fa-trash w-4 text-center"></i> Löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Rule Modal --}}
                        <div x-show="showRuleModal" x-cloak class="fixed z-[10000] flex items-center justify-center" style="top: 64px; bottom: 56px; left: 0; right: 0; padding: 8px;" @keydown.escape.window="if(showRuleModal) showRuleModal = false">
                            <div class="absolute inset-0 bg-black bg-opacity-50" @click="showRuleModal = false"></div>
                            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 flex flex-col" style="max-height: 100%;">
                                <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-xl flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-gray-900" x-text="editRuleId ? 'Regel bearbeiten' : 'Neue Benachrichtigungs-Regel'"></h4>
                                    <button @click="showRuleModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-lg"></i></button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-6">
                                    @livewire('customer.notification-rule-form', ['source' => 'travel-alert'], key('ta-rule-form'))
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Versandprotokoll --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5" x-on:reload-logs.window="loadLogs(1)" x-data="{
                        logs: [], logsMeta: {}, logsLoading: true, logsPage: 1,
                        async init() { await this.loadLogs(); },
                        async loadLogs(page) {
                            this.logsLoading = true;
                            if (page) this.logsPage = page;
                            try {
                                const r = await fetch('{{ route('customer.notification-settings.logs') }}?source=travel-alert&page=' + this.logsPage, { headers: { 'Accept': 'application/json' } });
                                if (r.ok) {
                                    const d = await r.json();
                                    this.logs = d.data || [];
                                    this.logsMeta = { current_page: d.current_page, last_page: d.last_page, total: d.total, from: d.from, to: d.to };
                                }
                            } catch(e) {}
                            this.logsLoading = false;
                        }
                    }">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900"><i class="fas fa-history mr-2 text-blue-500"></i>Versandprotokoll</h4>
                            <span class="text-[10px] text-gray-400" x-show="logsMeta.total" x-text="logsMeta.total + ' Einträge'"></span>
                        </div>

                        <div x-show="logsLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>

                        <div x-show="!logsLoading && logs.length === 0" class="text-center py-6 text-gray-500">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p class="text-xs">Noch keine Nachrichten versendet.</p>
                        </div>

                        <div x-show="!logsLoading && logs.length > 0">
                            <div class="space-y-2 mb-4">
                                <template x-for="log in logs" :key="log.id">
                                    <div class="border rounded-lg p-3 text-xs" :class="log.status === 'sent' ? 'border-gray-200' : 'border-red-200 bg-red-50'">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <i class="fas text-[10px]" :class="log.status === 'sent' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'"></i>
                                                    <span class="font-medium text-gray-900 truncate" x-text="log.subject"></span>
                                                    <span x-show="log.is_test" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-800">Test</span>
                                                </div>
                                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-gray-500">
                                                    <span><i class="fas fa-envelope mr-1"></i><span x-text="log.recipient_email"></span></span>
                                                    <span x-show="log.template_name"><i class="fas fa-file-alt mr-1"></i>Vorlage: <span x-text="log.template_name"></span></span>
                                                    <span x-show="log.rule_name"><i class="fas fa-list-check mr-1"></i>Regel: <span x-text="log.rule_name"></span></span>
                                                    <span x-show="log.notification_rule && !log.rule_name"><i class="fas fa-list-check mr-1"></i>Regel: <span x-text="log.notification_rule?.name"></span></span>
                                                </div>
                                                <p x-show="log.error_message" class="text-[10px] text-red-600 mt-1" x-text="log.error_message"></p>
                                            </div>
                                            <div class="text-[10px] text-gray-400 flex-shrink-0 text-right">
                                                <div x-text="new Date(log.created_at).toLocaleDateString('de-DE')"></div>
                                                <div x-text="new Date(log.created_at).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'})"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Paginator --}}
                            <div x-show="logsMeta.last_page > 1" class="flex items-center justify-between">
                                <p class="text-[10px] text-gray-500" x-text="'Seite ' + logsMeta.current_page + ' von ' + logsMeta.last_page"></p>
                                <div class="flex gap-1">
                                    <button @click="loadLogs(logsPage - 1)" :disabled="logsPage <= 1"
                                        :class="logsPage <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-2 py-1 text-[10px] rounded border border-gray-200">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <template x-for="p in logsMeta.last_page" :key="p">
                                        <button @click="loadLogs(p)"
                                            class="px-2 py-1 text-[10px] rounded border"
                                            :class="p === logsMeta.current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                                            x-text="p" x-show="Math.abs(p - logsMeta.current_page) < 3 || p === 1 || p === logsMeta.last_page">
                                        </button>
                                    </template>
                                    <button @click="loadLogs(logsPage + 1)" :disabled="logsPage >= logsMeta.last_page"
                                        :class="logsPage >= logsMeta.last_page ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'"
                                        class="px-2 py-1 text-[10px] rounded border border-gray-200">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Globale Toast Notification --}}
                    <div x-data="{ msg: '', type: 'success', visible: false }"
                         x-on:show-toast.window="msg = $event.detail.message; type = $event.detail.type; visible = true; if(type !== 'info') setTimeout(() => visible = false, 5000)">
                        <div x-show="visible" x-cloak x-transition
                             class="fixed top-20 right-6 max-w-sm z-[10001] rounded-lg shadow-lg border px-4 py-3 flex items-start gap-3"
                             :class="{ 'bg-green-50 border-green-200': type==='success', 'bg-red-50 border-red-200': type==='error', 'bg-blue-50 border-blue-200': type==='info' }">
                            <i class="fas mt-0.5" :class="{ 'fa-check-circle text-green-500': type==='success', 'fa-exclamation-circle text-red-500': type==='error', 'fa-spinner fa-spin text-blue-500': type==='info' }"></i>
                            <p class="flex-1 text-xs font-medium" :class="{ 'text-green-800': type==='success', 'text-red-800': type==='error', 'text-blue-800': type==='info' }" x-text="msg"></p>
                            <button @click="visible = false" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    @else
                    <div class="bg-white rounded-lg border border-gray-200 p-5 text-center">
                        <i class="fas fa-triangle-exclamation text-3xl text-gray-300 mb-2"></i>
                        <p class="text-xs text-gray-500">Travel Alert ist nicht aktiviert.</p>
                    </div>
                    @endif
                @endif

            @elseif($settingsSection === 'travel-link')
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Travel Link</h3>
                <p class="text-sm text-gray-500 mb-6">Verwalten Sie Ihre Travel Link Dienste.</p>

                @php
                    $tlTab = request()->query('tab', 'general');
                @endphp

                {{-- Tabs --}}
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex gap-6" aria-label="Tabs">
                            <a href="{{ route('customer.settings', ['section' => 'travel-link', 'tab' => 'general']) }}"
                               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $tlTab === 'general' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <i class="fas fa-cog mr-1.5"></i>Allgemeines
                            </a>
                            <a href="{{ route('customer.settings', ['section' => 'travel-link', 'tab' => 'notifications']) }}"
                               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $tlTab === 'notifications' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                <i class="fas fa-bell mr-1.5"></i>Benachrichtigungen
                            </a>
                        </nav>
                    </div>
                </div>

                @if($tlTab === 'general')
                {{-- Travel Link Wrapper --}}
                <div x-data="travelLinkManager()">

                {{-- Travel Link Aktivierung --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="hidden sm:block w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-link text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Travel Links aktivieren</p>
                                    <p class="text-xs text-gray-500 mt-1">Aktivieren Sie Travel Links, um Ihren Reisenden automatisch personalisierte Reiseinformationen per Link bereitzustellen.</p>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <button @click="toggleLinks()" :disabled="toggling"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
                                            :class="enabled ? 'bg-blue-600' : 'bg-gray-300'">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                              :class="enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                                    </button>
                                </div>
                            </div>

                            <div x-show="enabled" x-cloak class="mt-3 pt-3 border-t border-gray-100">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    <button @click="syncLinks()" :disabled="syncing"
                                            class="inline-flex items-center px-4 py-2 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                                            :class="syncing ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'">
                                        <i class="fas mr-1.5" :class="syncing ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                                        <span x-text="syncing ? 'Wird aktualisiert...' : 'Jetzt aktualisieren'"></span>
                                    </button>
                                    <div class="text-xs text-gray-500">
                                        <span x-show="lastSyncedAt">
                                            <i class="fas fa-clock mr-1"></i> Letzte Synchronisierung: <span x-text="lastSyncedAt"></span>
                                        </span>
                                        <span x-show="!lastSyncedAt">
                                            <i class="fas fa-info-circle mr-1"></i> Noch nicht synchronisiert
                                        </span>
                                    </div>
                                </div>

                                {{-- Sync-Ergebnis --}}
                                <div x-show="syncResult" x-cloak class="mt-3 p-3 rounded-lg text-xs"
                                     :class="syncSuccess ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'">
                                    <i class="fas mr-1" :class="syncSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                    <span x-text="syncResult"></span>
                                    <span x-show="syncStats" class="ml-2 text-gray-500" x-text="syncStats"></span>
                                </div>

                                {{-- Debug: Raw API Request/Response --}}
                                <div x-show="syncDebug" x-cloak class="mt-3 p-4 bg-gray-900 rounded-lg text-xs font-mono overflow-x-auto max-h-96 overflow-y-auto">
                                    <p class="text-gray-500 mb-2">API Request:</p>
                                    <p class="text-yellow-400" x-text="syncDebug?.api_request ? syncDebug.api_request.method + ' ' + syncDebug.api_request.url : ''"></p>
                                    <p class="text-gray-500 mt-2 mb-1">Request Headers:</p>
                                    <pre class="text-green-400" x-text="syncDebug?.api_request ? JSON.stringify(syncDebug.api_request.headers, null, 2) : ''"></pre>
                                    <p class="text-gray-500 mt-2 mb-1">Request Body:</p>
                                    <pre class="text-green-400" x-text="syncDebug?.api_request ? JSON.stringify(syncDebug.api_request.body, null, 2) : ''"></pre>
                                    <p class="text-gray-500 mt-3 mb-1">Response Status: <span class="text-cyan-400" x-text="syncDebug?.api_response?.status"></span></p>
                                    <p class="text-gray-500 mb-1">Response Body:</p>
                                    <pre class="text-green-400" x-text="syncDebug?.api_response ? JSON.stringify(syncDebug.api_response.body, null, 2) : ''"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alle Links lokal löschen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="hidden sm:block w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-trash-alt text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">Alle Links lokal löschen</p>
                            <p class="text-xs text-gray-500 mt-1">Entfernt alle lokal gespeicherten Travel Links und synchronisierten Reisedaten. Die Links bleiben auf der Passolution-Plattform bestehen.</p>
                            <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-3">
                                <button @click="deleteAllLinks()" :disabled="deleting"
                                        class="inline-flex items-center px-4 py-2 text-xs font-medium rounded-lg transition-colors whitespace-nowrap"
                                        :class="deleting ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700'">
                                    <i class="fas mr-1.5" :class="deleting ? 'fa-spinner fa-spin' : 'fa-trash-alt'"></i>
                                    <span x-text="deleting ? 'Wird gelöscht...' : 'Alle lokal löschen'"></span>
                                </button>
                                <div x-show="deleteResult" x-cloak class="text-xs"
                                     :class="deleteSuccess ? 'text-green-600' : 'text-red-600'">
                                    <i class="fas mr-1" :class="deleteSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                    <span x-text="deleteResult"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="enabled" x-cloak>
                {{-- Einreisebestimmungen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-passport text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Einreisebestimmungen</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Einreisebestimmungen</p>
                            <p class="text-xs text-gray-500 mt-1">Aktuelle Einreisebestimmungen und Visaanforderungen für Ihre Reiseziele. Automatische Prüfung basierend auf Nationalität und Reiseland.</p>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Visabestimmungen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-stamp text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Visabestimmungen</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Visabestimmungen</p>
                            <p class="text-xs text-gray-500 mt-1">Visaanforderungen und Beantragungsinformationen für Ihre Reiseziele. Prüfung der Visapflicht basierend auf Nationalität, Reisezweck und Aufenthaltsdauer.</p>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Sicherheitsinformationen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-shield-halved text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Sicherheitsinformationen</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Sicherheitsinformationen</p>
                            <p class="text-xs text-gray-500 mt-1">Aktuelle Sicherheitshinweise und Reisewarnungen für Ihre Zielländer. Risikobewertungen und Verhaltensempfehlungen.</p>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Gesundheitsinformationen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-heart-pulse text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Gesundheitsinformationen</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Gesundheitsinformationen</p>
                            <p class="text-xs text-gray-500 mt-1">Gesundheitshinweise, Impfempfehlungen und medizinische Informationen für Ihre Reiseziele. Aktuelle Hinweise zu Krankheitsrisiken vor Ort.</p>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Länderinformationen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-earth-europe text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Länderinformationen</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Länderinformationen</p>
                            <p class="text-xs text-gray-500 mt-1">Umfassende Länderinformationen mit allgemeinen Reisehinweisen, kulturellen Besonderheiten, Währung, Zeitzone und weiteren nützlichen Details für Ihre Reiseziele.</p>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Klimainformationen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-cloud-sun text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Klimainformationen</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                                <i class="fas fa-clock mr-1.5"></i> Bald verfügbar
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Klimainformationen</p>
                            <p class="text-xs text-gray-500 mt-1">Wetter- und Klimadaten für Ihre Reiseziele. Durchschnittliche Temperaturen, Niederschlag und beste Reisezeiten.</p>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                                <i class="fas fa-clock mr-1.5"></i> Bald verfügbar
                            </span>
                        </div>
                    </div>
                </div>
                </div>{{-- /x-show enabled --}}
                </div>{{-- /x-data travelLinkManager --}}

                @elseif($tlTab === 'notifications')
                <div class="bg-white rounded-lg border border-gray-200 p-5">
                    <div class="flex items-center gap-3 text-gray-400">
                        <i class="fas fa-bell text-2xl"></i>
                        <p class="text-sm">Dieser Bereich wird derzeit eingerichtet.</p>
                    </div>
                </div>
                @endif

            @elseif($settingsSection === 'travel-data')
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Travel Data</h3>
                <p class="text-sm text-gray-500 mb-6">Verwalten Sie Reisedaten und Datenquellen.</p>

                {{-- Reiseliste mit Tabs --}}
                <div class="mb-6" x-data="travelDataList()">
                    {{-- Tabs --}}
                    <div class="flex border-b border-gray-200 mb-4">
                        <button @click="switchTab('current')"
                                :class="tab === 'current' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                            <i class="fas fa-plane-departure mr-1.5"></i> Aktuell
                            <span x-show="typeof counts.current === 'number'" x-text="'(' + counts.current + ')'" class="ml-1 text-xs"></span>
                        </button>
                        <button @click="switchTab('upcoming')"
                                :class="tab === 'upcoming' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                            <i class="fas fa-calendar-alt mr-1.5"></i> Zukünftig
                            <span x-show="typeof counts.upcoming === 'number'" x-text="'(' + counts.upcoming + ')'" class="ml-1 text-xs"></span>
                        </button>
                        <button @click="switchTab('archive')"
                                :class="tab === 'archive' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                            <i class="fas fa-archive mr-1.5"></i> Archiv
                            <span x-show="typeof counts.archive === 'number'" x-text="'(' + counts.archive + ')'" class="ml-1 text-xs"></span>
                        </button>
                    </div>

                    {{-- Loading --}}
                    <div x-show="loading" class="flex items-center justify-center py-12">
                        <i class="fas fa-spinner fa-spin text-gray-400 text-xl mr-2"></i>
                        <span class="text-sm text-gray-500">Lade Reisen...</span>
                    </div>

                    {{-- Leere Liste --}}
                    <div x-show="!loading && folders.length === 0" x-cloak class="bg-white rounded-lg border border-gray-200 p-8 text-center">
                        <i class="fas fa-suitcase text-3xl text-gray-300 mb-3"></i>
                        <p class="text-sm text-gray-500">Keine Reisen in dieser Kategorie vorhanden.</p>
                    </div>

                    {{-- Reiseliste --}}
                    <div x-show="!loading && folders.length > 0" x-cloak>
                        <template x-for="folder in folders" :key="folder.id">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-3 hover:border-gray-300 transition-colors relative">
                                <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                                    {{-- Icon --}}
                                    <div class="hidden sm:block w-8 flex-shrink-0 pt-0.5 text-center">
                                        <i class="fas fa-suitcase-rolling text-xl text-gray-400"></i>
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-4">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="folder.folder_name || folder.folder_number"></p>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full whitespace-nowrap"
                                                      :class="statusClass(folder.status)"
                                                      x-text="statusLabel(folder.status)"></span>
                                                {{-- Kebab Menü --}}
                                                <div class="relative" x-data="{ menuOpen: false }" @click.outside="menuOpen = false">
                                                    <button @click.stop="menuOpen = !menuOpen" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <div x-show="menuOpen" x-cloak x-transition
                                                         class="absolute right-0 top-full mt-1 w-40 bg-white border border-gray-200 rounded-lg shadow-lg z-20">
                                                        <button @click="menuOpen = false; $dispatch('edit-folder', folder.id)"
                                                                class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-t-lg flex items-center gap-2">
                                                            <i class="fas fa-pen text-gray-400 text-xs"></i> Bearbeiten
                                                        </button>
                                                        <button @click="menuOpen = false; $dispatch('delete-folder', folder.id)"
                                                                class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-b-lg flex items-center gap-2">
                                                            <i class="fas fa-trash text-red-400 text-xs"></i> Löschen
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-gray-500">
                                            {{-- Datum --}}
                                            <span x-show="folder.travel_start_date">
                                                <i class="fas fa-calendar mr-1"></i>
                                                <span x-text="formatDate(folder.travel_start_date)"></span>
                                                <span x-show="folder.travel_end_date"> – <span x-text="formatDate(folder.travel_end_date)"></span></span>
                                            </span>

                                            {{-- Ziele --}}
                                            <span x-show="folder.destinations_visited && folder.destinations_visited.length > 0">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <span x-text="folder.destinations_visited ? folder.destinations_visited.join(', ') : ''"></span>
                                            </span>

                                            {{-- Teilnehmer --}}
                                            <span x-show="folder.total_participants > 0">
                                                <i class="fas fa-users mr-1"></i>
                                                <span x-text="folder.total_participants"></span> Teilnehmer
                                            </span>

                                            {{-- Vorgangsnummer --}}
                                            <span class="text-gray-400">
                                                <i class="fas fa-hashtag mr-1"></i>
                                                <span x-text="folder.folder_number"></span>
                                            </span>
                                        </div>

                                        {{-- Services --}}
                                        <div class="flex flex-wrap gap-2 mt-2" x-show="(folder.flight_services && folder.flight_services.length > 0) || (folder.hotel_services && folder.hotel_services.length > 0)">
                                            <template x-for="fs in (folder.flight_services || [])" :key="fs.id">
                                                <template x-for="seg in (fs.segments || [])" :key="seg.id">
                                                    <span class="inline-flex items-center px-2 py-0.5 text-xs bg-blue-50 text-blue-700 rounded">
                                                        <i class="fas fa-plane mr-1 text-[10px]"></i>
                                                        <span x-text="(seg.departure_airport_code || '') + ' → ' + (seg.arrival_airport_code || '')"></span>
                                                        <span class="ml-1 text-blue-500" x-show="seg.departure_time" x-text="formatDateTime(seg.departure_time)"></span>
                                                    </span>
                                                </template>
                                            </template>
                                            <template x-for="hs in (folder.hotel_services || [])" :key="hs.id">
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs bg-amber-50 text-amber-700 rounded">
                                                    <i class="fas fa-hotel mr-1 text-[10px]"></i>
                                                    <span x-text="hs.hotel_name || hs.country_code || 'Hotel'"></span>
                                                    <span class="ml-1 text-amber-500" x-show="hs.check_in_date" x-text="formatDateRange(hs.check_in_date, hs.check_out_date)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Pagination --}}
                        <div x-show="lastPage > 1" class="flex items-center justify-between mt-4">
                            <p class="text-xs text-gray-500">
                                Seite <span x-text="currentPage"></span> von <span x-text="lastPage"></span>
                                (<span x-text="total"></span> Reisen)
                            </p>
                            <div class="flex gap-2">
                                <button @click="loadPage(currentPage - 1)" :disabled="currentPage <= 1"
                                        :class="currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                        class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button @click="loadPage(currentPage + 1)" :disabled="currentPage >= lastPage"
                                        :class="currentPage >= lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                        class="px-3 py-1.5 text-xs border border-gray-200 rounded-lg">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                    {{-- Delete Confirm --}}
                    <div x-show="deleteConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="deleteConfirm = false">
                        <div class="fixed inset-0 bg-black/50" @click="deleteConfirm = false"></div>
                        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6" @click.stop>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
                                    <i class="fas fa-trash text-red-500 text-lg"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Reise löschen?</h3>
                                <p class="text-sm text-gray-500 mb-6">Diese Aktion kann nicht rückgängig gemacht werden. Alle zugehörigen Teilnehmer, Flüge und Hotels werden ebenfalls gelöscht.</p>
                                <div class="flex gap-3 justify-center">
                                    <button @click="deleteConfirm = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Abbrechen</button>
                                    <button @click="confirmDelete()" class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700">Endgültig löschen</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Modal --}}
                    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="editModal = false">
                        <div class="fixed inset-0 bg-black/50" @click="editModal = false"></div>
                        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl mx-4 p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-lg font-semibold text-gray-900">Reise bearbeiten</h3>
                                <button @click="editModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-xmark text-lg"></i></button>
                            </div>

                            <div x-show="editResult" x-cloak class="mb-4 p-4 rounded-lg"
                                 :class="editSuccess ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                                <p class="text-xs" :class="editSuccess ? 'text-green-800' : 'text-red-800'">
                                    <i class="fas mr-1" :class="editSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                    <span x-text="editResult"></span>
                                </p>
                            </div>

                            {{-- Step Indicator --}}
                            <div class="flex items-center mb-6 border-b border-gray-200 pb-4">
                                <template x-for="(s, i) in [{n:'Reisende',icon:'fa-users'},{n:'Flüge',icon:'fa-plane'},{n:'Hotels',icon:'fa-hotel'}]" :key="i">
                                    <div class="flex items-center" :class="i > 0 ? 'ml-2' : ''">
                                        <div x-show="i > 0" class="w-8 h-px bg-gray-300 mx-1"></div>
                                        <button @click="editStep = i + 1" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                                :class="editStep === i + 1 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-gray-500 hover:text-gray-700'">
                                            <i class="fas text-[10px]" :class="s.icon"></i> <span x-text="s.n"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            {{-- Step 1: Reisende --}}
                            <div x-show="editStep === 1">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Reisende</h4>
                                    <button @click="editTravellers.push({salutation:'Herr',first_name:'',last_name:'',dob:'',nationality:'DE',email:'',phone:''})" type="button" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Hinzufügen
                                    </button>
                                </div>
                                <template x-for="(t, ti) in editTravellers" :key="ti">
                                    <div class="border border-gray-200 rounded-lg p-4 mb-3 relative">
                                        <button x-show="editTravellers.length > 1" @click="editTravellers.splice(ti,1)" type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs"><i class="fas fa-trash"></i></button>
                                        <div class="text-xs font-medium text-gray-500 mb-3">Reisender <span x-text="ti+1"></span></div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Anrede</label>
                                                <select x-model="t.salutation" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                    <option value="Herr">Herr</option><option value="Frau">Frau</option><option value="Divers">Divers</option><option value="Kind">Kind</option><option value="Baby">Baby</option>
                                                </select>
                                            </div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Vorname *</label><input type="text" x-model="t.first_name" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Nachname *</label><input type="text" x-model="t.last_name" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Geburtsdatum</label><input type="date" x-model="t.dob" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Nationalität</label><input type="text" x-model="t.nationality" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" maxlength="2"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">E-Mail</label><input type="email" x-model="t.email" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Telefon</label><input type="text" x-model="t.phone" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Step 2: Flüge --}}
                            <div x-show="editStep === 2">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Flüge</h4>
                                    <button @click="editFlights.push({dep_code:'',dep_time:'',arr_code:'',arr_time:'',airline:'',flight_nr:'',dep_terminal:'',arr_terminal:''})" type="button" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Hinzufügen
                                    </button>
                                </div>
                                <template x-for="(f, fi) in editFlights" :key="fi">
                                    <div class="border border-gray-200 rounded-lg p-4 mb-3 relative">
                                        <button @click="editFlights.splice(fi,1)" type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs"><i class="fas fa-trash"></i></button>
                                        <div class="text-xs font-medium text-gray-500 mb-3"><i class="fas fa-plane mr-1"></i> Flug <span x-text="fi+1"></span></div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Abflug-Airport *</label><input type="text" x-model="f.dep_code" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" maxlength="3"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Abflug Datum/Zeit *</label><input type="datetime-local" x-model="f.dep_time" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Ankunft-Airport *</label><input type="text" x-model="f.arr_code" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" maxlength="3"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Ankunft Datum/Zeit *</label><input type="datetime-local" x-model="f.arr_time" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Airline-Code</label><input type="text" x-model="f.airline" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" maxlength="2"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Flugnummer</label><input type="text" x-model="f.flight_nr" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Terminal Ab</label><input type="text" x-model="f.dep_terminal" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Terminal An</label><input type="text" x-model="f.arr_terminal" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="editFlights.length === 0" class="text-center py-6 text-gray-400 text-xs">
                                    <i class="fas fa-plane text-2xl mb-2"></i><p>Keine Flüge</p>
                                </div>
                            </div>

                            {{-- Step 3: Hotels --}}
                            <div x-show="editStep === 3">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Hotels</h4>
                                    <button @click="editHotels.push({name:'',check_in:'',check_out:'',country:'',city:'',room_type:'',board:''})" type="button" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Hinzufügen
                                    </button>
                                </div>
                                <template x-for="(h, hi) in editHotels" :key="hi">
                                    <div class="border border-gray-200 rounded-lg p-4 mb-3 relative">
                                        <button @click="editHotels.splice(hi,1)" type="button" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs"><i class="fas fa-trash"></i></button>
                                        <div class="text-xs font-medium text-gray-500 mb-3"><i class="fas fa-hotel mr-1"></i> Hotel <span x-text="hi+1"></span></div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div class="col-span-2"><label class="block text-[11px] text-gray-500 mb-1">Hotelname *</label><input type="text" x-model="h.name" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Check-in *</label><input type="datetime-local" x-model="h.check_in" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Check-out *</label><input type="datetime-local" x-model="h.check_out" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Land (ISO) *</label><input type="text" x-model="h.country" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" maxlength="2"></div>
                                            <div><label class="block text-[11px] text-gray-500 mb-1">Stadt</label><input type="text" x-model="h.city" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Zimmertyp</label>
                                                <select x-model="h.room_type" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                    <option value="">--</option><option value="Single">Einzelzimmer</option><option value="Double">Doppelzimmer</option><option value="Suite">Suite</option><option value="Twin">Zweibettzimmer</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Verpflegung</label>
                                                <select x-model="h.board" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                    <option value="">--</option><option value="OV">Ohne</option><option value="BB">Frühstück</option><option value="HB">Halbpension</option><option value="FB">Vollpension</option><option value="AI">All Inclusive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="editHotels.length === 0" class="text-center py-6 text-gray-400 text-xs">
                                    <i class="fas fa-hotel text-2xl mb-2"></i><p>Keine Hotels</p>
                                </div>
                            </div>

                            {{-- Booking Reference --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="block text-[11px] text-gray-500 mb-1">Buchungsreferenz</label><input type="text" x-model="editBookingRef" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs"></div>
                                </div>
                            </div>

                            {{-- Navigation --}}
                            <div class="flex justify-between mt-6 pt-4 border-t border-gray-200">
                                <button x-show="editStep > 1" @click="editStep--" type="button" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 flex items-center gap-2">
                                    <i class="fas fa-arrow-left"></i> Zurück
                                </button>
                                <div x-show="editStep === 1" class="w-1"></div>
                                <div class="flex gap-3">
                                    <button @click="editModal = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Abbrechen</button>
                                    <button x-show="editStep < 3" @click="editStep++" type="button" class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                                        Weiter <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <button x-show="editStep === 3" @click="saveEdit()" :disabled="editSaving"
                                            :class="editSaving ? 'bg-gray-400 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700'"
                                            class="px-4 py-2 text-sm text-white rounded-lg flex items-center gap-2">
                                        <i class="fas" :class="editSaving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                        <span x-text="editSaving ? 'Wird gespeichert...' : 'Speichern'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                {{-- Reise aus JSON erstellen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5" x-data="travelDataImport()">
                    <div class="flex items-start gap-4">
                        <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-file-import text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">Reise aus JSON erstellen</p>
                            <p class="text-xs text-gray-500 mt-1">Erstellen Sie eine neue Reise aus einem JSON-Payload. Die Reise wird automatisch in der Travel Alert Übersicht angezeigt und mit aktuellen Sicherheitsereignissen abgeglichen.</p>
                        </div>
                        <div class="w-32 flex-shrink-0 flex justify-end">
                            <button @click="showImport = true" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 whitespace-nowrap">
                                <i class="fas fa-plus mr-1.5"></i> Neu aus JSON
                            </button>
                        </div>
                    </div>

                    {{-- Import Modal --}}
                    <div x-show="showImport" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showImport = false">
                        <div class="fixed inset-0 bg-black/50" @click="showImport = false"></div>
                        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-lg font-semibold text-gray-900">Reise aus JSON erstellen</h3>
                                <button @click="showImport = false" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-xmark text-lg"></i>
                                </button>
                            </div>

                            {{-- Erfolgs-/Fehlermeldung --}}
                            <div x-show="resultMessage" x-cloak class="mb-4 p-4 rounded-lg"
                                 :class="resultSuccess ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                                <p class="text-xs" :class="resultSuccess ? 'text-green-800' : 'text-red-800'">
                                    <i class="fas mr-1" :class="resultSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                    <span x-text="resultMessage"></span>
                                </p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">JSON Payload</label>
                                <textarea x-model="jsonPayload" rows="18"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs font-mono focus:ring-blue-500 focus:border-blue-500"
                                          placeholder='{"provider": {"id": "...", "sent_at": "..."}, "trip": {...}}'></textarea>
                                <p class="mt-1 text-xs text-gray-500">Das JSON muss dem Travel Detail Schema entsprechen (mit provider, trip, travellers und itinerary).</p>
                            </div>

                            <div class="flex justify-between">
                                <div class="relative" x-data="{ exampleOpen: false }" @click.outside="exampleOpen = false">
                                    <button type="button" @click="exampleOpen = !exampleOpen"
                                            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 flex items-center gap-2">
                                        <i class="fas fa-lightbulb"></i> Beispiel <i class="fas fa-chevron-down text-xs ml-1"></i>
                                    </button>
                                    <div x-show="exampleOpen" x-cloak x-transition
                                         class="absolute left-0 bottom-full mb-1 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                        <button type="button" @click="loadExample('current'); exampleOpen = false"
                                                class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-t-lg flex items-center gap-2">
                                            <i class="fas fa-plane-departure text-gray-400"></i> Beispiel Aktuell
                                        </button>
                                        <button type="button" @click="loadExample('future'); exampleOpen = false"
                                                class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-b-lg flex items-center gap-2">
                                            <i class="fas fa-calendar-plus text-gray-400"></i> Beispiel Zukünftig
                                        </button>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <button type="button" @click="showImport = false"
                                            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                        Abbrechen
                                    </button>
                                    <button @click="importTrip" :disabled="loading || !jsonPayload.trim()"
                                            :class="loading || !jsonPayload.trim() ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                                            class="px-4 py-2 text-sm text-white rounded-lg flex items-center gap-2">
                                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-file-import'"></i>
                                        <span x-text="loading ? 'Wird importiert...' : 'Importieren'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reise manuell erstellen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mt-5" x-data="manualTripCreate()">
                    <div class="flex items-start gap-4">
                        <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-pen-to-square text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">Reise manuell erstellen</p>
                            <p class="text-xs text-gray-500 mt-1">Erstellen Sie eine neue Reise über ein Formular mit Reisenden, Flügen und Hotels.</p>
                        </div>
                        <div class="w-32 flex-shrink-0 flex justify-end">
                            <button @click="showForm = true" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-xs rounded-lg hover:bg-emerald-700 whitespace-nowrap">
                                <i class="fas fa-plus mr-1.5"></i> Neue Reise
                            </button>
                        </div>
                    </div>

                    {{-- Formular Modal --}}
                    <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center" @keydown.escape.window="showForm = false">
                        <div class="fixed inset-0 bg-black/50" @click="showForm = false"></div>
                        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl mx-4 p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-lg font-semibold text-gray-900">Reise manuell erstellen</h3>
                                <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-xmark text-lg"></i>
                                </button>
                            </div>

                            {{-- Erfolgs-/Fehlermeldung --}}
                            <div x-show="resultMessage" x-cloak class="mb-4 p-4 rounded-lg"
                                 :class="resultSuccess ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                                <p class="text-xs" :class="resultSuccess ? 'text-green-800' : 'text-red-800'">
                                    <i class="fas mr-1" :class="resultSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                                    <span x-text="resultMessage"></span>
                                </p>
                            </div>

                            {{-- Step Indicator --}}
                            <div class="flex items-center mb-6 border-b border-gray-200 pb-4">
                                <template x-for="(s, i) in [{n:'Reisende',icon:'fa-users'},{n:'Flüge',icon:'fa-plane'},{n:'Hotels',icon:'fa-hotel'}]" :key="i">
                                    <div class="flex items-center" :class="i > 0 ? 'ml-2' : ''">
                                        <div v-if="i > 0" x-show="i > 0" class="w-8 h-px bg-gray-300 mx-1"></div>
                                        <button @click="step = i + 1" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                                :class="step === i + 1 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-gray-500 hover:text-gray-700'">
                                            <i class="fas text-[10px]" :class="s.icon"></i>
                                            <span x-text="s.n"></span>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            {{-- Step 1: Reisende --}}
                            <div x-show="step === 1">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Reisende</h4>
                                    <button @click="addTraveller()" type="button" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Reisenden hinzufügen
                                    </button>
                                </div>
                                <template x-for="(t, ti) in travellers" :key="ti">
                                    <div class="border border-gray-200 rounded-lg p-4 mb-3 relative">
                                        <button x-show="travellers.length > 1" @click="travellers.splice(ti, 1)" type="button"
                                                class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <div class="text-xs font-medium text-gray-500 mb-3">Reisender <span x-text="ti + 1"></span></div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Anrede</label>
                                                <select x-model="t.salutation" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                    <option value="Herr">Herr</option>
                                                    <option value="Frau">Frau</option>
                                                    <option value="Divers">Divers</option>
                                                    <option value="Kind">Kind</option>
                                                    <option value="Baby">Baby</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Vorname *</label>
                                                <input type="text" x-model="t.first_name" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Max">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Nachname *</label>
                                                <input type="text" x-model="t.last_name" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Mustermann">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Geburtsdatum</label>
                                                <input type="date" x-model="t.dob" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Nationalität</label>
                                                <input type="text" x-model="t.nationality" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="DE" maxlength="2">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">E-Mail</label>
                                                <input type="email" x-model="t.email" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="max@beispiel.de">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Telefon</label>
                                                <input type="text" x-model="t.phone" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="+49 170 ...">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Step 2: Flüge --}}
                            <div x-show="step === 2">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Flüge</h4>
                                    <button @click="addFlight()" type="button" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Flug hinzufügen
                                    </button>
                                </div>
                                <template x-for="(f, fi) in flights" :key="fi">
                                    <div class="border border-gray-200 rounded-lg p-4 mb-3 relative">
                                        <button x-show="flights.length > 1" @click="flights.splice(fi, 1)" type="button"
                                                class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <div class="text-xs font-medium text-gray-500 mb-3">
                                            <i class="fas fa-plane mr-1"></i> Flug <span x-text="fi + 1"></span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Abflug-Airport *</label>
                                                <input type="text" x-model="f.dep_code" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" placeholder="FRA" maxlength="3">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Abflug Datum/Zeit *</label>
                                                <input type="datetime-local" x-model="f.dep_time" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Ankunft-Airport *</label>
                                                <input type="text" x-model="f.arr_code" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" placeholder="BCN" maxlength="3">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Ankunft Datum/Zeit *</label>
                                                <input type="datetime-local" x-model="f.arr_time" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Airline-Code</label>
                                                <input type="text" x-model="f.airline" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" placeholder="LH" maxlength="2">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Flugnummer</label>
                                                <input type="text" x-model="f.flight_nr" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="1124">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Terminal Ab</label>
                                                <input type="text" x-model="f.dep_terminal" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="1">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Terminal An</label>
                                                <input type="text" x-model="f.arr_terminal" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="A">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="flights.length === 0" class="text-center py-6 text-gray-400 text-xs">
                                    <i class="fas fa-plane text-2xl mb-2"></i>
                                    <p>Noch keine Flüge hinzugefügt</p>
                                </div>
                            </div>

                            {{-- Step 3: Hotels --}}
                            <div x-show="step === 3">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700">Hotels</h4>
                                    <button @click="addHotel()" type="button" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Hotel hinzufügen
                                    </button>
                                </div>
                                <template x-for="(h, hi) in hotels" :key="hi">
                                    <div class="border border-gray-200 rounded-lg p-4 mb-3 relative">
                                        <button x-show="hotels.length > 1" @click="hotels.splice(hi, 1)" type="button"
                                                class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xs">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <div class="text-xs font-medium text-gray-500 mb-3">
                                            <i class="fas fa-hotel mr-1"></i> Hotel <span x-text="hi + 1"></span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div class="col-span-2">
                                                <label class="block text-[11px] text-gray-500 mb-1">Hotelname *</label>
                                                <input type="text" x-model="h.name" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Hotel Arts Barcelona">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Check-in *</label>
                                                <input type="datetime-local" x-model="h.check_in" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Check-out *</label>
                                                <input type="datetime-local" x-model="h.check_out" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Land (ISO) *</label>
                                                <input type="text" x-model="h.country" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs uppercase" placeholder="ES" maxlength="2">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Stadt</label>
                                                <input type="text" x-model="h.city" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Barcelona">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Zimmertyp</label>
                                                <select x-model="h.room_type" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                    <option value="">-- wählen --</option>
                                                    <option value="Single">Einzelzimmer</option>
                                                    <option value="Double">Doppelzimmer</option>
                                                    <option value="Suite">Suite</option>
                                                    <option value="Twin">Zweibettzimmer</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] text-gray-500 mb-1">Verpflegung</label>
                                                <select x-model="h.board" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                    <option value="">-- wählen --</option>
                                                    <option value="OV">Ohne Verpflegung</option>
                                                    <option value="BB">Frühstück</option>
                                                    <option value="HB">Halbpension</option>
                                                    <option value="FB">Vollpension</option>
                                                    <option value="AI">All Inclusive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="hotels.length === 0" class="text-center py-6 text-gray-400 text-xs">
                                    <i class="fas fa-hotel text-2xl mb-2"></i>
                                    <p>Noch keine Hotels hinzugefügt</p>
                                </div>
                            </div>

                            {{-- Booking Reference --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] text-gray-500 mb-1">Buchungsreferenz</label>
                                        <input type="text" x-model="bookingRef" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="ABC123">
                                    </div>
                                </div>
                            </div>

                            {{-- Navigation & Submit --}}
                            <div class="flex justify-between mt-6 pt-4 border-t border-gray-200">
                                <button x-show="step > 1" @click="step--" type="button"
                                        class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 flex items-center gap-2">
                                    <i class="fas fa-arrow-left"></i> Zurück
                                </button>
                                <div x-show="step === 1" class="w-1"></div>
                                <div class="flex gap-3">
                                    <button type="button" @click="showForm = false"
                                            class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                        Abbrechen
                                    </button>
                                    <button x-show="step < 3" @click="step++" type="button"
                                            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                                        Weiter <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <button x-show="step === 3" @click="submitTrip()" :disabled="saving"
                                            :class="saving ? 'bg-gray-400 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700'"
                                            class="px-4 py-2 text-sm text-white rounded-lg flex items-center gap-2">
                                        <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                        <span x-text="saving ? 'Wird erstellt...' : 'Reise erstellen'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($settingsSection === 'connected-services')
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Connected Services</h3>
                <p class="text-sm text-gray-500 mb-6">Verwalten Sie Ihre verbundenen Dienste und Integrationen.</p>

                {{-- Business Visum --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-stamp text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Business Visum</span>
                            @if($featureService->isFeatureEnabled('navigation_business_visa_enabled', $customer))
                                <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                    <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                                </span>
                            @else
                                <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                    <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                                </span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Business Visum</p>
                            <p class="text-xs text-gray-500 mt-1">Visum-Service für Geschäftsreisende. Beantragung, Statusverfolgung und Verwaltung von Geschäftsvisa direkt über die Plattform.</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 mt-3">
                                <div class="sm:w-1/3">
                                    <img src="{{ asset('images/connected_services/workflex.png') }}" alt="Business Visum" style="height: 34px; width: auto; margin-left: -7px;">
                                </div>
                                <div class="sm:w-2/3">
                                    <a href="https://www.workflex.com/hr-glossary/business-travel" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-external-link-alt mr-1.5"></i> Website Anbieter
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            @if($featureService->isFeatureEnabled('navigation_business_visa_enabled', $customer))
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-green-50 text-green-700 border border-green-200 whitespace-nowrap">
                                    <i class="fas fa-circle-check mr-1.5"></i> Aktiv
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                    <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Wallet --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-wallet text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Wallet</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Wallet</p>
                            <p class="text-xs text-gray-500 mt-1">Digitale Wallet-Integration für Reisedokumente, Bordkarten und Versicherungsnachweise. Alle wichtigen Dokumente immer griffbereit auf dem Smartphone.</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 mt-3">
                                <div class="sm:w-1/3">
                                    <img src="{{ asset('images/connected_services/eloyalty.webp') }}" alt="Wallet" style="height: 22px; width: auto;">
                                </div>
                                <div class="sm:w-2/3">
                                    <a href="https://eloyalty.io/" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-external-link-alt mr-1.5"></i> Website Anbieter
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- E-SIM --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 mb-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-sim-card text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">E-SIM</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">E-SIM</p>
                            <p class="text-xs text-gray-500 mt-1">E-SIM-Service für mobile Datenverbindungen im Ausland. Automatische Bereitstellung von Datentarifen passend zum Reiseziel ohne physische SIM-Karte.</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 mt-3">
                                <div class="sm:w-1/3">
                                    <img src="{{ asset('images/connected_services/bubby.svg') }}" alt="Bubby" style="height: 40px; width: auto;">
                                </div>
                                <div class="sm:w-2/3">
                                    <a href="https://www.hubbyesim.com/" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-external-link-alt mr-1.5"></i> Website Anbieter
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Doctors Network --}}
                <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                        <div class="flex items-center gap-3 sm:block sm:w-8 sm:flex-shrink-0 sm:pt-0.5 sm:text-center">
                            <i class="fas fa-user-doctor text-2xl text-gray-400"></i>
                            <span class="sm:hidden text-sm font-medium text-gray-700">Doctors Network</span>
                            <span class="sm:hidden ml-auto inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="hidden sm:block text-sm font-medium text-gray-700">Doctors Network</p>
                            <p class="text-xs text-gray-500 mt-1">Zugang zu einem weltweiten Netzwerk von Ärzten und medizinischen Einrichtungen. Schnelle Hilfe vor Ort bei gesundheitlichen Notfällen auf Reisen.</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 mt-3">
                                <div class="sm:w-1/3">
                                    <img src="{{ asset('images/connected_services/mybakup.png') }}" alt="myBakup" style="height: 50px; width: auto;">
                                </div>
                                <div class="sm:w-2/3">
                                    <a href="https://www.app.mybakup.com/traveler/" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                                        <i class="fas fa-external-link-alt mr-1.5"></i> Website Anbieter
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="hidden sm:flex w-32 flex-shrink-0 justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                    </div>
                </div>

            @elseif($settingsSection === 'travel-information')
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Travel Information</h3>
                <p class="text-sm text-gray-500 mb-6">Länder- und Flughafeninformationen für Ihre Reisenden.</p>

                {{-- Länder --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                    <div class="flex items-start gap-4">
                        <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-earth-americas text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">Länder</p>
                            <p class="text-xs text-gray-500 mt-1">Umfassende Länderinformationen mit Einreisebestimmungen, Visaanforderungen, Gesundheitshinweisen, Sicherheitsbewertungen und allgemeinen Reiseinformationen.</p>
                        </div>
                        <div class="w-32 flex-shrink-0 flex justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Flughäfen --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5">
                    <div class="flex items-start gap-4">
                        <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-plane-departure text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">Flughäfen</p>
                            <p class="text-xs text-gray-500 mt-1">Detaillierte Flughafeninformationen mit Airlines, Hotels, Lounges, Transfermöglichkeiten und weiteren Services vor Ort.</p>
                        </div>
                        <div class="w-32 flex-shrink-0 flex justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Airlines --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5">
                    <div class="flex items-start gap-4">
                        <div class="w-8 flex-shrink-0 pt-0.5 text-center">
                            <i class="fas fa-plane text-2xl text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">Airlines</p>
                            <p class="text-xs text-gray-500 mt-1">Informationen zu Fluggesellschaften mit Streckennetz, Gepäckbestimmungen, Kontaktdaten und weiteren servicerelevanten Details.</p>
                        </div>
                        <div class="w-32 flex-shrink-0 flex justify-end">
                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-50 text-gray-500 border border-gray-200 whitespace-nowrap">
                                <i class="fas fa-circle-minus mr-1.5"></i> Inaktiv
                            </span>
                        </div>
                    </div>
                </div>

            @endif

            {{-- Success/Error Messages --}}
            <div x-show="message" x-cloak x-transition
                 class="fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg text-sm z-50"
                 :class="messageType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
                 x-text="message"
                 @click="message = null"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function apiTokenManager() {
    return {
        generatedToken: '',
        hasToken: false,
        loading: false,
        copied: false,
        async init() {
            try {
                const r = await fetch('{{ route('customer.api-tokens.status') }}', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                const d = await r.json();
                if (d.success) this.hasToken = d.has_token;
            } catch(e) {}
        },
        async generateToken() {
            if (this.loading) return;
            if (this.hasToken && !confirm('Das Generieren eines neuen Tokens widerruft automatisch den alten Token. Fortfahren?')) return;
            this.loading = true;
            try {
                const r = await fetch('{{ route('customer.api-tokens.generate') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                const d = await r.json();
                if (d.success) { this.generatedToken = d.token; this.hasToken = true; this.copied = false; }
                else alert('Fehler: ' + (d.message || 'Unbekannter Fehler'));
            } catch(e) { alert('Fehler beim Generieren des Tokens.'); }
            this.loading = false;
        },
        async revokeToken() {
            if (!confirm('Möchten Sie den API-Token wirklich widerrufen?')) return;
            this.loading = true;
            try {
                const r = await fetch('{{ route('customer.api-tokens.revoke') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                const d = await r.json();
                if (d.success) { this.generatedToken = ''; this.hasToken = false; alert('Token wurde erfolgreich widerrufen.'); }
                else alert('Fehler: ' + (d.message || 'Unbekannter Fehler'));
            } catch(e) { alert('Fehler beim Widerrufen des Tokens.'); }
            this.loading = false;
        },
        async copyToken() {
            try { await navigator.clipboard.writeText(this.generatedToken); this.copied = true; setTimeout(() => this.copied = false, 2000); }
            catch(e) { alert('Fehler beim Kopieren.'); }
        }
    };
}

function travelLinkManager() {
    return {
        enabled: {{ $customer->travel_links_enabled ? 'true' : 'false' }},
        toggling: false,
        syncing: false,
        lastSyncedAt: {!! $customer->pds_last_synced_at ? "'" . $customer->pds_last_synced_at->format('d.m.Y H:i') . "'" : 'null' !!},
        syncResult: null,
        syncSuccess: false,
        syncStats: null,
        syncDebug: null,
        deleting: false,
        deleteResult: null,
        deleteSuccess: false,
        async deleteAllLinks() {
            if (!confirm('Alle lokal gespeicherten Travel Links und Reisedaten wirklich löschen?')) return;
            this.deleting = true;
            this.deleteResult = null;
            try {
                const r = await fetch('{{ route('customer.travel-data.delete-all-links') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                this.deleteSuccess = d.success;
                this.deleteResult = d.message;
            } catch (e) {
                this.deleteSuccess = false;
                this.deleteResult = 'Verbindungsfehler';
            }
            this.deleting = false;
        },
        async toggleLinks() {
            this.toggling = true;
            try {
                const r = await fetch('{{ route('customer.travel-data.toggle-travel-links') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                if (d.success) {
                    this.enabled = d.enabled;
                    this.syncResult = null;
                }
            } catch (e) {}
            this.toggling = false;
        },
        async syncLinks() {
            this.syncing = true;
            this.syncResult = null;
            this.syncStats = null;
            this.syncDebug = null;
            try {
                const r = await fetch('{{ route('customer.travel-data.sync-links') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                this.syncSuccess = d.success;
                this.syncResult = d.message;
                if (d.debug) {
                    this.syncDebug = d;
                } else {
                    if (d.stats) {
                        const s = d.stats;
                        let parts = [`${s.trips_synced} Reisen`];
                        if (s.trips_created > 0) parts.push(`${s.trips_created} neu`);
                        if (s.trips_updated > 0) parts.push(`${s.trips_updated} aktualisiert`);
                        if (s.links_created > 0) parts.push(`${s.links_created} neue Links`);
                        if (s.links_existing > 0) parts.push(`${s.links_existing} unverändert`);
                        this.syncStats = `(${parts.join(', ')})`;
                    }
                    if (d.synced_at) {
                        this.lastSyncedAt = d.synced_at;
                    }
                }
            } catch (e) {
                this.syncSuccess = false;
                this.syncResult = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
            }
            this.syncing = false;
        }
    };
}

function jsonImportManager() {
    return {
        showModal: false,
        jsonPayload: '',
        importing: false,
        importResult: null,
        importSuccess: false,
        loadExample() {
            const startDate = new Date();
            startDate.setDate(startDate.getDate() + 7);
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + 10);

            const fmt = (d, h = '10:00') => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}T${h}:00`;
            };

            const checkIn = new Date(startDate);
            const checkOut = new Date(endDate);
            const returnDate = new Date(endDate);

            const example = {
                trip: {
                    travellers: [
                        {
                            name: { salutation: "Herr", first: "Max", last: "Mustermann" },
                            date_of_birth: "1985-06-15",
                            nationality: "DE",
                            contact: { email: "max@example.com", phone: "+49 170 1234567" }
                        },
                        {
                            name: { salutation: "Frau", first: "Anna", last: "Gruber" },
                            date_of_birth: "1990-03-22",
                            nationality: "AT",
                            contact: { email: "anna@example.com", phone: "+43 660 9876543" }
                        }
                    ],
                    itinerary: [
                        {
                            type: "travel",
                            segments: [
                                {
                                    departure: {
                                        airport: { code: "FRA", country_code: "DE" },
                                        time: fmt(startDate, '08:30'),
                                        terminal: "1"
                                    },
                                    arrival: {
                                        airport: { code: "PMI", country_code: "ES" },
                                        time: fmt(startDate, '11:15'),
                                        terminal: "A"
                                    },
                                    marketing_carrier: { airline_code: "LH", flight_number: "1802" }
                                }
                            ]
                        },
                        {
                            type: "stay",
                            check_in: fmt(checkIn, '15:00'),
                            check_out: fmt(checkOut, '11:00'),
                            location: {
                                name: "Hotel Playa de Palma",
                                country_code: "ES",
                                city: "Palma de Mallorca"
                            }
                        },
                        {
                            type: "travel",
                            segments: [
                                {
                                    departure: {
                                        airport: { code: "PMI", country_code: "ES" },
                                        time: fmt(returnDate, '14:00'),
                                        terminal: "A"
                                    },
                                    arrival: {
                                        airport: { code: "FRA", country_code: "DE" },
                                        time: fmt(returnDate, '16:45'),
                                        terminal: "1"
                                    },
                                    marketing_carrier: { airline_code: "LH", flight_number: "1803" }
                                }
                            ]
                        }
                    ]
                }
            };

            this.jsonPayload = JSON.stringify(example, null, 2);
            this.importResult = null;
        },
        async importJson() {
            if (!this.jsonPayload.trim()) return;
            this.importing = true;
            this.importResult = null;
            try {
                const r = await fetch('{{ route("customer.travel-data.import-json") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ json_payload: this.jsonPayload })
                });
                const d = await r.json();
                this.importSuccess = d.success;
                this.importResult = d.message;
                if (d.success) {
                    setTimeout(() => {
                        this.showModal = false;
                        this.jsonPayload = '';
                        this.importResult = null;
                        window.dispatchEvent(new CustomEvent('travel-data-reload'));
                    }, 1500);
                }
            } catch (e) {
                this.importSuccess = false;
                this.importResult = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
            }
            this.importing = false;
        }
    };
}

function pdsSyncManager() {
    return {
        enabled: {{ $customer->pds_sync_enabled ? 'true' : 'false' }},
        toggling: false,
        syncing: false,
        lastSyncedAt: {!! $customer->pds_last_synced_at ? "'" . $customer->pds_last_synced_at->format('d.m.Y H:i') . "'" : 'null' !!},
        syncResult: null,
        syncSuccess: false,
        syncStats: null,
        async toggle() {
            this.toggling = true;
            try {
                const r = await fetch('{{ route('customer.travel-data.sync-toggle') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                if (d.success) {
                    this.enabled = d.enabled;
                    this.syncResult = null;
                }
            } catch (e) {}
            this.toggling = false;
        },
        async syncNow() {
            this.syncing = true;
            this.syncResult = null;
            this.syncStats = null;
            try {
                const r = await fetch('{{ route('customer.travel-data.sync-now') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await r.json();
                this.syncSuccess = d.success;
                this.syncResult = d.message;
                if (d.stats) {
                    this.syncStats = `(${d.stats.duration_ms}ms)`;
                }
                if (d.synced_at) {
                    this.lastSyncedAt = d.synced_at;
                }
            } catch (e) {
                this.syncSuccess = false;
                this.syncResult = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
            }
            this.syncing = false;
        }
    };
}

function travelDataList() {
    return {
        tab: 'current',
        folders: [],
        loading: false,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        counts: { current: null, upcoming: null, archive: null },
        switchTab(t) {
            this.tab = t;
            this.currentPage = 1;
            this.loadFolders();
        },
        loadPage(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.loadFolders();
        },
        async loadFolders() {
            this.loading = true;
            try {
                const r = await fetch(`{{ route('customer.travel-data.folders') }}?tab=${this.tab}&page=${this.currentPage}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const d = await r.json();
                this.folders = d.data || [];
                this.currentPage = d.current_page;
                this.lastPage = d.last_page;
                this.total = d.total;
                this.counts[this.tab] = d.total;
            } catch (e) {
                this.folders = [];
            }
            this.loading = false;
        },
        async loadCounts() {
            for (const t of ['current', 'upcoming', 'archive']) {
                if (t === this.tab) continue;
                try {
                    const r = await fetch(`{{ route('customer.travel-data.folders') }}?tab=${t}&page=1`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const d = await r.json();
                    this.counts[t] = d.total;
                } catch (e) {}
            }
        },
        formatDate(d) {
            if (!d) return '';
            const dt = new Date(d);
            if (isNaN(dt)) return '';
            return dt.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
        },
        formatDateTime(dt) {
            if (!dt) return '';
            const d = new Date(dt);
            if (isNaN(d)) return '';
            return d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ', ' + d.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        },
        formatDateRange(from, to) {
            if (!from) return '';
            const f = new Date(from);
            if (isNaN(f)) return '';
            let result = f.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            if (to) {
                const t = new Date(to);
                if (!isNaN(t)) result += ' – ' + t.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            }
            return result;
        },
        statusClass(s) {
            return {
                'draft': 'bg-gray-100 text-gray-600',
                'confirmed': 'bg-blue-50 text-blue-700',
                'active': 'bg-green-50 text-green-700',
                'completed': 'bg-gray-100 text-gray-600',
                'cancelled': 'bg-red-50 text-red-600',
            }[s] || 'bg-gray-100 text-gray-600';
        },
        statusLabel(s) {
            return {
                'draft': 'Entwurf',
                'confirmed': 'Bestätigt',
                'active': 'Aktiv',
                'completed': 'Abgeschlossen',
                'cancelled': 'Storniert',
            }[s] || s;
        },

        // Edit/Delete
        editModal: false,
        deleteConfirm: false,
        editId: null,
        deleteId: null,
        editSaving: false,
        editResult: '',
        editSuccess: false,
        editStep: 1,
        editBookingRef: '',
        editTravellers: [],
        editFlights: [],
        editHotels: [],

        init() {
            this.loadFolders();
            this.loadCounts();
            window.addEventListener('travel-data-reload', () => { this.loadFolders(); this.loadCounts(); });
            window.addEventListener('edit-folder', (e) => this.openEdit(e.detail));
            window.addEventListener('delete-folder', (e) => { this.deleteId = e.detail; this.deleteConfirm = true; });
        },

        salutationReverse(code) {
            return { mr: 'Herr', mrs: 'Frau', child: 'Kind', infant: 'Baby', diverse: 'Divers' }[code] || 'Herr';
        },
        async openEdit(id) {
            this.editId = id;
            this.editResult = '';
            this.editStep = 1;
            this.editSaving = false;
            try {
                const r = await fetch(`{{ url('customer/travel-data/folders') }}/${id}`, { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                const f = d.folder;
                this.editBookingRef = (f.itineraries && f.itineraries[0]) ? f.itineraries[0].booking_reference || '' : '';
                this.editTravellers = (f.participants || []).map(p => ({
                    salutation: this.salutationReverse(p.salutation),
                    first_name: p.first_name || '',
                    last_name: p.last_name || '',
                    dob: p.birth_date ? p.birth_date.substring(0, 10) : '',
                    nationality: p.nationality || '',
                    email: p.email || '',
                    phone: p.phone || '',
                }));
                if (this.editTravellers.length === 0) this.editTravellers = [{ salutation: 'Herr', first_name: '', last_name: '', dob: '', nationality: 'DE', email: '', phone: '' }];

                this.editFlights = [];
                (f.flight_services || []).forEach(fs => {
                    (fs.segments || []).forEach(seg => {
                        const fmtDt = (v) => v ? v.replace(/\.000000Z$/, '').replace(/Z$/, '').substring(0, 16) : '';
                        this.editFlights.push({
                            dep_code: seg.departure_airport_code || '',
                            dep_time: fmtDt(seg.departure_time),
                            arr_code: seg.arrival_airport_code || '',
                            arr_time: fmtDt(seg.arrival_time),
                            airline: seg.airline_code || '',
                            flight_nr: seg.flight_number || '',
                            dep_terminal: seg.departure_terminal || '',
                            arr_terminal: seg.arrival_terminal || '',
                        });
                    });
                });

                this.editHotels = (f.hotel_services || []).map(h => {
                    const fmtD = (v) => v ? v.substring(0, 10) + 'T14:00' : '';
                    const fmtD2 = (v) => v ? v.substring(0, 10) + 'T11:00' : '';
                    return {
                        name: h.hotel_name || '',
                        check_in: h.check_in_date ? fmtD(h.check_in_date) : '',
                        check_out: h.check_out_date ? fmtD2(h.check_out_date) : '',
                        country: h.country_code || '',
                        city: h.city || '',
                        room_type: h.room_type || '',
                        board: h.board_type || '',
                    };
                });

                this.editModal = true;
            } catch (e) {
                this.editResult = 'Fehler beim Laden der Reise.';
                this.editSuccess = false;
            }
        },
        async saveEdit() {
            this.editSaving = true;
            this.editResult = '';
            try {
                const r = await fetch(`{{ url('customer/travel-data/folders') }}/${this.editId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        booking_reference: this.editBookingRef,
                        travellers: this.editTravellers,
                        flights: this.editFlights.filter(f => f.dep_code && f.arr_code && f.dep_time && f.arr_time),
                        hotels: this.editHotels.filter(h => h.name && h.check_in && h.check_out && h.country),
                    }),
                });
                const d = await r.json();
                if (d.success) {
                    this.editSuccess = true;
                    this.editResult = d.message;
                    this.loadFolders();
                    setTimeout(() => { this.editModal = false; }, 1500);
                } else {
                    this.editSuccess = false;
                    this.editResult = d.message || 'Fehler beim Speichern.';
                }
            } catch (e) {
                this.editSuccess = false;
                this.editResult = 'Verbindungsfehler.';
            }
            this.editSaving = false;
        },
        async confirmDelete() {
            try {
                const r = await fetch(`{{ url('customer/travel-data/folders') }}/${this.deleteId}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                });
                const d = await r.json();
                if (d.success) {
                    this.loadFolders();
                    this.loadCounts();
                }
            } catch (e) {}
            this.deleteConfirm = false;
            this.deleteId = null;
        },
    };
}

function travelDataImport() {
    return {
        showImport: false,
        jsonPayload: '',
        loading: false,
        resultMessage: '',
        resultSuccess: false,
        loadExample(type = 'current') {
            const today = new Date();
            const rand = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
            const pad = (n) => String(n).padStart(2, '0');
            const fmt = (d, h, m) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(h)}:${pad(m)}:00+01:00`;

            const dep = new Date(today);
            const ret = new Date(today);

            if (type === 'current') {
                dep.setDate(dep.getDate() - rand(1, 3));
                ret.setDate(ret.getDate() + rand(3, 7));
            } else {
                dep.setDate(dep.getDate() + rand(14, 60));
                ret.setDate(dep.getDate() + rand(4, 10));
            }

            const depH = rand(6, 11);
            const depM = [0, 15, 30, 45][rand(0, 3)];
            const retH = rand(12, 19);
            const retM = [0, 15, 30, 45][rand(0, 3)];

            const routes = [
                { from: 'FRA', fromLat: 50.0379, fromLng: 8.5622, to: 'BCN', toLat: 41.2974, toLng: 2.0833, cc: 'ES', al: 'LH', fn1: '1124', fn2: '1125', hotels: [
                    { name: 'Hotel Arts Barcelona', lat: 41.3879, lng: 2.1942 },
                    { name: 'W Barcelona', lat: 41.3686, lng: 2.1893 },
                    { name: 'Mandarin Oriental Barcelona', lat: 41.3916, lng: 2.1650 },
                ]},
                { from: 'MUC', fromLat: 48.3537, fromLng: 11.7750, to: 'JFK', toLat: 40.6413, toLng: -73.7781, cc: 'US', al: 'LH', fn1: '410', fn2: '411', hotels: [
                    { name: 'The Plaza New York', lat: 40.7645, lng: -73.9746 },
                    { name: 'The Waldorf Astoria New York', lat: 40.7565, lng: -73.9737 },
                    { name: 'Park Hyatt New York', lat: 40.7648, lng: -73.9718 },
                ]},
                { from: 'ZRH', fromLat: 47.4647, fromLng: 8.5492, to: 'NRT', toLat: 35.7720, toLng: 140.3929, cc: 'JP', al: 'LX', fn1: '160', fn2: '161', hotels: [
                    { name: 'Park Hyatt Tokyo', lat: 35.6855, lng: 139.6906 },
                    { name: 'The Peninsula Tokyo', lat: 35.6752, lng: 139.7630 },
                    { name: 'Aman Tokyo', lat: 35.6872, lng: 139.7635 },
                ]},
                { from: 'VIE', fromLat: 48.1103, fromLng: 16.5697, to: 'DXB', toLat: 25.2532, toLng: 55.3657, cc: 'AE', al: 'OS', fn1: '881', fn2: '882', hotels: [
                    { name: 'Burj Al Arab Jumeirah', lat: 25.1412, lng: 55.1853 },
                    { name: 'Atlantis The Palm', lat: 25.1304, lng: 55.1172 },
                    { name: 'Armani Hotel Dubai', lat: 25.1972, lng: 55.2744 },
                ]},
                { from: 'HAM', fromLat: 53.6304, fromLng: 9.9882, to: 'LIS', toLat: 38.7756, toLng: -9.1354, cc: 'PT', al: 'TP', fn1: '561', fn2: '562', hotels: [
                    { name: 'Pestana Palace Lisboa', lat: 38.7023, lng: -9.1780 },
                    { name: 'Four Seasons Hotel Ritz Lisbon', lat: 38.7277, lng: -9.1516 },
                    { name: 'Bairro Alto Hotel', lat: 38.7107, lng: -9.1449 },
                ]},
                { from: 'DUS', fromLat: 51.2895, fromLng: 6.7668, to: 'ATH', toLat: 37.9364, toLng: 23.9445, cc: 'GR', al: 'LH', fn1: '3380', fn2: '3381', hotels: [
                    { name: 'Hotel Grande Bretagne Athens', lat: 37.9755, lng: 23.7348 },
                    { name: 'King George Athens', lat: 37.9753, lng: 23.7344 },
                    { name: 'Electra Palace Athens', lat: 37.9733, lng: 23.7290 },
                ]},
                { from: 'BER', fromLat: 52.3667, fromLng: 13.5033, to: 'IST', toLat: 41.2753, toLng: 28.7519, cc: 'TR', al: 'TK', fn1: '1724', fn2: '1725', hotels: [
                    { name: 'Four Seasons Hotel Istanbul at Sultanahmet', lat: 41.0064, lng: 28.9784 },
                    { name: 'Ciragan Palace Kempinski Istanbul', lat: 41.0460, lng: 29.0212 },
                    { name: 'Raffles Istanbul', lat: 41.0445, lng: 29.0100 },
                ]},
                { from: 'FRA', fromLat: 50.0379, fromLng: 8.5622, to: 'BKK', toLat: 13.6900, toLng: 100.7501, cc: 'TH', al: 'TG', fn1: '921', fn2: '922', hotels: [
                    { name: 'Mandarin Oriental Bangkok', lat: 13.7237, lng: 100.5133 },
                    { name: 'The Peninsula Bangkok', lat: 13.7220, lng: 100.5098 },
                    { name: 'Shangri-La Bangkok', lat: 13.7210, lng: 100.5155 },
                ]},
                { from: 'MUC', fromLat: 48.3537, fromLng: 11.7750, to: 'PMI', toLat: 39.5517, toLng: 2.7388, cc: 'ES', al: 'LH', fn1: '1802', fn2: '1803', hotels: [
                    { name: 'Castillo Hotel Son Vida', lat: 39.6010, lng: 2.6180 },
                    { name: 'Hotel St. Regis Mardavall', lat: 39.5296, lng: 2.5194 },
                    { name: 'Belmond La Residencia Deia', lat: 39.7490, lng: 2.7490 },
                ]},
                { from: 'ZRH', fromLat: 47.4647, fromLng: 8.5492, to: 'SIN', toLat: 1.3644, toLng: 103.9915, cc: 'SG', al: 'SQ', fn1: '345', fn2: '346', hotels: [
                    { name: 'Marina Bay Sands Singapore', lat: 1.2834, lng: 103.8607 },
                    { name: 'Raffles Hotel Singapore', lat: 1.2949, lng: 103.8543 },
                    { name: 'The Fullerton Hotel Singapore', lat: 1.2865, lng: 103.8530 },
                ]},
                { from: 'VIE', fromLat: 48.1103, fromLng: 16.5697, to: 'CPT', toLat: -33.9715, toLng: 18.6021, cc: 'ZA', al: 'OS', fn1: '57', fn2: '58', hotels: [
                    { name: 'One&Only Cape Town', lat: -33.9083, lng: 18.4176 },
                    { name: 'Belmond Mount Nelson Hotel', lat: -33.9362, lng: 18.4095 },
                    { name: 'Table Bay Hotel', lat: -33.9033, lng: 18.4218 },
                ]},
                { from: 'BER', fromLat: 52.3667, fromLng: 13.5033, to: 'CDG', toLat: 49.0097, toLng: 2.5479, cc: 'FR', al: 'AF', fn1: '1035', fn2: '1036', hotels: [
                    { name: 'Le Meurice Paris', lat: 48.8651, lng: 2.3281 },
                    { name: 'Shangri-La Hotel Paris', lat: 48.8630, lng: 2.2935 },
                    { name: 'Hotel Plaza Athenee Paris', lat: 48.8660, lng: 2.3040 },
                ]},
                { from: 'HAM', fromLat: 53.6304, fromLng: 9.9882, to: 'LHR', toLat: 51.4700, toLng: -0.4543, cc: 'GB', al: 'BA', fn1: '967', fn2: '968', hotels: [
                    { name: 'The Savoy London', lat: 51.5103, lng: -0.1205 },
                    { name: 'Claridges London', lat: 51.5126, lng: -0.1473 },
                    { name: 'The Ritz London', lat: 51.5072, lng: -0.1416 },
                ]},
                { from: 'DUS', fromLat: 51.2895, fromLng: 6.7668, to: 'FCO', toLat: 41.8003, toLng: 12.2389, cc: 'IT', al: 'LH', fn1: '240', fn2: '241', hotels: [
                    { name: 'Hotel de Russie Rome', lat: 41.9094, lng: 12.4763 },
                    { name: 'Hotel Hassler Roma', lat: 41.9062, lng: 12.4831 },
                    { name: 'Hotel Eden Rome', lat: 41.9065, lng: 12.4876 },
                ]},
            ];
            const r = routes[rand(0, routes.length - 1)];
            const h = r.hotels[rand(0, r.hotels.length - 1)];

            const example = {
                "schema_version": "1.1",
                "provider": {
                    "id": "manual-import",
                    "name": "Manueller Import",
                    "sent_at": new Date().toISOString()
                },
                "trip": {
                    "external_trip_id": "TRIP-" + Date.now(),
                    "booking_reference": String.fromCharCode(rand(65,90)) + String.fromCharCode(rand(65,90)) + String.fromCharCode(rand(65,90)) + rand(100, 999),
                    "travellers": [
                        {
                            "external_traveller_id": "PAX-001",
                            "type": "adult",
                            "name": { "salutation": "Herr", "first": "Max", "last": "Mustermann" },
                            "date_of_birth": "1985-06-15",
                            "nationality": "DE",
                            "contact": { "email": "max.mustermann@beispiel.de", "phone": "+49 170 1234567" },
                            "passport": { "country": "DE" }
                        },
                        {
                            "external_traveller_id": "PAX-002",
                            "type": "adult",
                            "name": { "salutation": "Frau", "first": "Anna", "last": "Meier" },
                            "date_of_birth": "1990-03-22",
                            "nationality": "CH",
                            "contact": { "email": "anna.meier@beispiel.ch", "phone": "+41 79 1234567" },
                            "passport": { "country": "CH" }
                        }
                    ],
                    "itinerary": [
                        {
                            "type": "travel",
                            "mode": "air",
                            "leg_id": "LEG-OUT",
                            "segments": [
                                {
                                    "segment_id": "SEG-OUT-1",
                                    "departure": {
                                        "airport": { "code": r.from, "geocode": { "lat": r.fromLat, "lng": r.fromLng } },
                                        "time": fmt(dep, depH, depM),
                                        "terminal": "1"
                                    },
                                    "arrival": {
                                        "airport": { "code": r.to, "geocode": { "lat": r.toLat, "lng": r.toLng } },
                                        "time": fmt(dep, depH + 2 + rand(0, 6), depM),
                                        "terminal": "1"
                                    },
                                    "marketing_carrier": { "airline_code": r.al, "flight_number": r.fn1 },
                                    "operating_carrier": { "airline_code": r.al },
                                    "transfer_role_hint": "none"
                                }
                            ]
                        },
                        {
                            "type": "stay",
                            "stay_id": "STAY-001",
                            "stay_type": "hotel",
                            "location": {
                                "name": h.name,
                                "geocode": { "lat": h.lat, "lng": h.lng },
                                "country_code": r.cc
                            },
                            "check_in": fmt(dep, 14, 0),
                            "check_out": fmt(ret, 11, 0)
                        },
                        {
                            "type": "travel",
                            "mode": "air",
                            "leg_id": "LEG-RET",
                            "segments": [
                                {
                                    "segment_id": "SEG-RET-1",
                                    "departure": {
                                        "airport": { "code": r.to, "geocode": { "lat": r.toLat, "lng": r.toLng } },
                                        "time": fmt(ret, retH, retM),
                                        "terminal": "1"
                                    },
                                    "arrival": {
                                        "airport": { "code": r.from, "geocode": { "lat": r.fromLat, "lng": r.fromLng } },
                                        "time": fmt(ret, retH + 2 + rand(0, 6), retM),
                                        "terminal": "1"
                                    },
                                    "marketing_carrier": { "airline_code": r.al, "flight_number": r.fn2 },
                                    "operating_carrier": { "airline_code": r.al },
                                    "transfer_role_hint": "none"
                                }
                            ]
                        }
                    ]
                }
            };

            this.jsonPayload = JSON.stringify(example, null, 2);
            this.resultMessage = '';
        },
        async importTrip() {
            this.loading = true;
            this.resultMessage = '';
            try {
                const r = await fetch('{{ route('customer.travel-data.import-json') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ json_payload: this.jsonPayload })
                });
                const d = await r.json();
                if (d.success) {
                    this.resultSuccess = true;
                    this.resultMessage = d.message + ' (Vorgangsnummer: ' + d.folder_number + ')';
                    this.jsonPayload = '';
                } else {
                    this.resultSuccess = false;
                    this.resultMessage = d.message || 'Unbekannter Fehler';
                }
            } catch (e) {
                this.resultSuccess = false;
                this.resultMessage = 'Fehler beim Import. Bitte versuchen Sie es erneut.';
            }
            this.loading = false;
        }
    };
}

function manualTripCreate() {
    return {
        showForm: false,
        step: 1,
        saving: false,
        resultMessage: '',
        resultSuccess: false,
        bookingRef: '',
        travellers: [{ salutation: 'Herr', first_name: '', last_name: '', dob: '', nationality: 'DE', email: '', phone: '' }],
        flights: [{ dep_code: '', dep_time: '', arr_code: '', arr_time: '', airline: '', flight_nr: '', dep_terminal: '', arr_terminal: '' }],
        hotels: [],
        addTraveller() {
            this.travellers.push({ salutation: 'Herr', first_name: '', last_name: '', dob: '', nationality: 'DE', email: '', phone: '' });
        },
        addFlight() {
            this.flights.push({ dep_code: '', dep_time: '', arr_code: '', arr_time: '', airline: '', flight_nr: '', dep_terminal: '', arr_terminal: '' });
        },
        addHotel() {
            this.hotels.push({ name: '', check_in: '', check_out: '', country: '', city: '', room_type: '', board: '' });
        },
        buildJson() {
            const travellers = this.travellers.filter(t => t.first_name && t.last_name).map((t, i) => ({
                external_traveller_id: 'PAX-' + String(i + 1).padStart(3, '0'),
                type: ['Kind', 'Baby'].includes(t.salutation) ? (t.salutation === 'Baby' ? 'infant' : 'child') : 'adult',
                name: { salutation: t.salutation, first: t.first_name, last: t.last_name },
                date_of_birth: t.dob || undefined,
                nationality: t.nationality || undefined,
                contact: (t.email || t.phone) ? { email: t.email || undefined, phone: t.phone || undefined } : undefined,
                passport: t.nationality ? { country: t.nationality } : undefined,
            }));

            const itinerary = [];
            this.flights.filter(f => f.dep_code && f.arr_code && f.dep_time && f.arr_time).forEach((f, i) => {
                itinerary.push({
                    type: 'travel',
                    mode: 'air',
                    leg_id: 'LEG-' + (i + 1),
                    segments: [{
                        segment_id: 'SEG-' + (i + 1),
                        departure: {
                            airport: { code: f.dep_code.toUpperCase() },
                            time: f.dep_time + ':00',
                            terminal: f.dep_terminal || undefined,
                        },
                        arrival: {
                            airport: { code: f.arr_code.toUpperCase() },
                            time: f.arr_time + ':00',
                            terminal: f.arr_terminal || undefined,
                        },
                        marketing_carrier: (f.airline || f.flight_nr) ? {
                            airline_code: f.airline ? f.airline.toUpperCase() : undefined,
                            flight_number: f.flight_nr || undefined,
                        } : undefined,
                    }],
                });
            });
            this.hotels.filter(h => h.name && h.check_in && h.check_out && h.country).forEach((h, i) => {
                itinerary.push({
                    type: 'stay',
                    stay_id: 'STAY-' + (i + 1),
                    stay_type: 'hotel',
                    check_in: h.check_in + ':00',
                    check_out: h.check_out + ':00',
                    location: {
                        name: h.name,
                        country_code: h.country.toUpperCase(),
                        city: h.city || undefined,
                    },
                });
            });

            return {
                schema_version: '1.1',
                provider: { id: 'manual-form', name: 'Manuelle Eingabe', sent_at: new Date().toISOString() },
                trip: {
                    external_trip_id: 'TRIP-' + Date.now(),
                    booking_reference: this.bookingRef || undefined,
                    travellers,
                    itinerary,
                },
            };
        },
        async submitTrip() {
            if (!this.travellers.some(t => t.first_name && t.last_name)) {
                this.resultSuccess = false;
                this.resultMessage = 'Bitte mindestens einen Reisenden mit Vor- und Nachname angeben.';
                this.step = 1;
                return;
            }
            if (!this.flights.some(f => f.dep_code && f.arr_code && f.dep_time && f.arr_time) && !this.hotels.some(h => h.name && h.check_in && h.check_out && h.country)) {
                this.resultSuccess = false;
                this.resultMessage = 'Bitte mindestens einen Flug oder ein Hotel angeben.';
                return;
            }
            this.saving = true;
            this.resultMessage = '';
            try {
                const payload = this.buildJson();
                const r = await fetch('{{ route("customer.travel-data.import-json") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ json_payload: JSON.stringify(payload) }),
                });
                const d = await r.json();
                if (d.success) {
                    this.resultSuccess = true;
                    this.resultMessage = d.message + ' (Vorgangsnummer: ' + d.folder_number + ')';
                    this.travellers = [{ salutation: 'Herr', first_name: '', last_name: '', dob: '', nationality: 'DE', email: '', phone: '' }];
                    this.flights = [{ dep_code: '', dep_time: '', arr_code: '', arr_time: '', airline: '', flight_nr: '', dep_terminal: '', arr_terminal: '' }];
                    this.hotels = [];
                    this.bookingRef = '';
                    this.step = 1;
                    window.dispatchEvent(new CustomEvent('travel-data-reload'));
                } else {
                    this.resultSuccess = false;
                    this.resultMessage = d.message || 'Unbekannter Fehler';
                }
            } catch (e) {
                this.resultSuccess = false;
                this.resultMessage = 'Fehler beim Erstellen. Bitte versuchen Sie es erneut.';
            }
            this.saving = false;
        },
    };
}

function copyGtmCode(type) {
    const el = document.getElementById('gtm-code-' + type);
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        const btn = event.target.closest('button');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
        btn.classList.add('text-green-700', 'bg-green-50');
        setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('text-green-700', 'bg-green-50'); }, 2000);
    });
}

function settingsManager() {
    return {
        editSection: null,
        message: null,
        messageType: 'success',

        passwords: {
            current_password: '',
            password: '',
            password_confirmation: '',
        },

        personal: {
            name: @json($isEmployeeLogin ? $loggedInEmployee->first_name . ' ' . $loggedInEmployee->last_name : $customer->name),
            email: @json($isEmployeeLogin ? $loggedInEmployee->email : $customer->email),
            phone: @json($isEmployeeLogin ? ($loggedInEmployee->phone ?? '') : ($customer->phone ?? '')),
        },

        company: {
            company_name: @json($customer->company_name ?? ''),
            company_additional: @json($customer->company_additional ?? ''),
            company_street: @json($customer->company_street ?? ''),
            company_house_number: @json($customer->company_house_number ?? ''),
            company_postal_code: @json($customer->company_postal_code ?? ''),
            company_city: @json($customer->company_city ?? ''),
            company_country: @json($customer->company_country ?? ''),
        },

        billing: {
            billing_company_name: @json($customer->billing_company_name ?? ''),
            billing_additional: @json($customer->billing_additional ?? ''),
            billing_street: @json($customer->billing_street ?? ''),
            billing_house_number: @json($customer->billing_house_number ?? ''),
            billing_postal_code: @json($customer->billing_postal_code ?? ''),
            billing_city: @json($customer->billing_city ?? ''),
            billing_country: @json($customer->billing_country ?? ''),
        },

        showMessage(text, type = 'success') {
            this.message = text;
            this.messageType = type;
            setTimeout(() => this.message = null, 3000);
        },

        async uploadAvatar(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                this.showMessage('Datei ist zu groß (max. 2 MB)', 'error');
                return;
            }
            const formData = new FormData();
            formData.append('avatar', file);
            try {
                const res = await fetch('{{ route("customer.profile.upload-avatar") }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('avatar-preview').innerHTML = '<img src="' + data.avatar_url + '" alt="Profilbild" class="w-full h-full object-cover">';
                    this.showMessage('Profilbild hochgeladen');
                    setTimeout(() => location.reload(), 500);
                } else {
                    this.showMessage(data.message || 'Fehler beim Hochladen', 'error');
                }
            } catch (e) {
                this.showMessage('Fehler beim Hochladen', 'error');
            }
            event.target.value = '';
        },

        async deleteAvatar() {
            if (!confirm('Profilbild wirklich entfernen?')) return;
            try {
                const res = await fetch('{{ route("customer.profile.delete-avatar") }}', {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.success) {
                    this.showMessage('Profilbild entfernt');
                    setTimeout(() => location.reload(), 500);
                }
            } catch (e) {
                this.showMessage('Fehler beim Entfernen', 'error');
            }
        },

        async changePassword() {
            if (this.passwords.password !== this.passwords.password_confirmation) {
                this.showMessage('Passwörter stimmen nicht überein', 'error');
                return;
            }
            try {
                const res = await fetch('{{ route("customer.profile.update-password") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.passwords)
                });
                const data = await res.json();
                if (data.success) {
                    this.passwords = { current_password: '', password: '', password_confirmation: '' };
                    this.showMessage('Passwort erfolgreich geändert');
                } else {
                    this.showMessage(data.message || 'Fehler beim Ändern', 'error');
                }
            } catch (e) {
                this.showMessage('Fehler beim Ändern des Passworts', 'error');
            }
        },

        async savePersonal() {
            try {
                const res = await fetch('{{ route("customer.profile.update-personal") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.personal)
                });
                const data = await res.json();
                if (data.success) {
                    this.editSection = null;
                    this.showMessage('Persönliche Daten gespeichert');
                } else {
                    this.showMessage(data.message || 'Fehler beim Speichern', 'error');
                }
            } catch (e) {
                this.showMessage('Fehler beim Speichern', 'error');
            }
        },

        async saveCompany() {
            try {
                const res = await fetch('{{ route("customer.profile.update-company-address") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.company)
                });
                const data = await res.json();
                if (data.success) {
                    this.editSection = null;
                    this.showMessage('Firmenanschrift gespeichert');
                } else {
                    this.showMessage(data.message || 'Fehler beim Speichern', 'error');
                }
            } catch (e) {
                this.showMessage('Fehler beim Speichern', 'error');
            }
        },

        async saveBilling() {
            try {
                const res = await fetch('{{ route("customer.profile.update-billing-address") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.billing)
                });
                const data = await res.json();
                if (data.success) {
                    this.editSection = null;
                    this.showMessage('Rechnungsadresse gespeichert');
                } else {
                    this.showMessage(data.message || 'Fehler beim Speichern', 'error');
                }
            } catch (e) {
                this.showMessage('Fehler beim Speichern', 'error');
            }
        }
    };
}

function customerTypeManager() {
    return {
        customerType: @json($customer->customer_type ?: 'business'),
        businessTypes: @json($customer->business_type ?? []),
        async updateCustomerType(type) {
            this.customerType = type;
            try {
                await fetch('{{ route("customer.profile.update-customer-type") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ customer_type: type })
                });
            } catch (e) {}
        },
        isBusinessTypeSelected(type) { return this.businessTypes.includes(type); },
        async toggleBusinessType(type) {
            if (this.businessTypes.includes(type)) {
                this.businessTypes = this.businessTypes.filter(t => t !== type);
            } else {
                this.businessTypes.push(type);
            }
            try {
                await fetch('{{ route("customer.profile.update-business-type") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ business_types: this.businessTypes })
                });
            } catch (e) {}
        }
    };
}

function twoFactorManager() {
    return {
        setupStarted: false,
        qrCodeSvg: '',
        secretKey: '',
        confirmCode: '',
        recoveryCodes: [],
        confirmDisable: false,
        password: '',
        error: '',

        isOAuthUser: {{ $customer->provider ? 'true' : 'false' }},

        async confirmPassword() {
            if (this.isOAuthUser) {
                // OAuth-User: Passwort-Bestätigung mit leerem Passwort (Server lässt durch)
                try {
                    const res = await fetch('/customer/user/confirm-password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ password: '' })
                    });
                    return res.ok || res.status === 201;
                } catch (e) { return false; }
            }

            const pw = prompt('Bitte geben Sie Ihr Passwort ein, um fortzufahren:');
            if (!pw) return false;
            try {
                const res = await fetch('/customer/user/confirm-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ password: pw })
                });
                return res.ok || res.status === 201;
            } catch (e) { return false; }
        },

        async enable2FA() {
            this.error = '';
            if (!(await this.confirmPassword())) {
                this.error = 'Passwort ist nicht korrekt.';
                return;
            }
            try {
                const res = await fetch('/customer/user/two-factor-authentication', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }
                });
                if (res.ok || res.status === 200) {
                    this.setupStarted = true;
                    await this.loadQrCode();
                    await this.loadSecretKey();
                } else {
                    this.error = 'Fehler beim Aktivieren der 2FA.';
                }
            } catch (e) {
                this.error = 'Fehler beim Aktivieren der 2FA.';
            }
        },

        async loadQrCode() {
            try {
                const res = await fetch('/customer/user/two-factor-qr-code', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.qrCodeSvg = data.svg;
                }
            } catch (e) {}
        },

        async loadSecretKey() {
            try {
                const res = await fetch('/customer/user/two-factor-secret-key', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.secretKey = data.secretKey;
                }
            } catch (e) {}
        },

        async confirm2FA() {
            this.error = '';
            try {
                const res = await fetch('/customer/user/confirmed-two-factor-authentication', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ code: this.confirmCode })
                });
                if (res.ok || res.status === 200) {
                    await this.loadRecoveryCodes();
                } else {
                    const data = await res.json().catch(() => null);
                    this.error = data?.message || 'Der Code ist ungültig. Bitte versuchen Sie es erneut.';
                }
            } catch (e) {
                this.error = 'Fehler bei der Bestätigung.';
            }
        },

        async cancel2FA() {
            try {
                await fetch('/customer/user/two-factor-authentication', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }
                });
            } catch (e) {}
            this.setupStarted = false;
            this.qrCodeSvg = '';
            this.secretKey = '';
            this.confirmCode = '';
            this.error = '';
        },

        async disable2FA() {
            this.error = '';
            try {
                const res = await fetch('/customer/user/two-factor-authentication', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ password: this.password })
                });
                if (res.ok || res.status === 200) {
                    location.reload();
                } else {
                    this.error = 'Passwort ist nicht korrekt.';
                }
            } catch (e) {
                this.error = 'Fehler beim Deaktivieren.';
            }
        },

        async showRecoveryCodes() {
            await this.loadRecoveryCodes();
        },

        async loadRecoveryCodes() {
            try {
                const res = await fetch('/customer/user/two-factor-recovery-codes', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (res.ok) {
                    this.recoveryCodes = await res.json();
                }
            } catch (e) {}
        },

        async regenerateRecoveryCodes() {
            try {
                await fetch('/customer/user/two-factor-recovery-codes', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }
                });
                await this.loadRecoveryCodes();
            } catch (e) {}
        }
    };
}

function masterDataManager() {
    return {
        mdTab: 'uebersicht',
        editSection: null,
        message: null,
        messageType: 'success',

        company: {
            company_name: @json($customer->company_name ?? ''),
            company_additional: @json($customer->company_additional ?? ''),
            company_street: @json($customer->company_street ?? ''),
            company_house_number: @json($customer->company_house_number ?? ''),
            company_postal_code: @json($customer->company_postal_code ?? ''),
            company_city: @json($customer->company_city ?? ''),
            company_country: @json($customer->company_country ?? ''),
        },

        billing: {
            billing_company_name: @json($customer->billing_company_name ?? ''),
            billing_additional: @json($customer->billing_additional ?? ''),
            billing_street: @json($customer->billing_street ?? ''),
            billing_house_number: @json($customer->billing_house_number ?? ''),
            billing_postal_code: @json($customer->billing_postal_code ?? ''),
            billing_city: @json($customer->billing_city ?? ''),
            billing_country: @json($customer->billing_country ?? ''),
        },

        showMessage(text, type = 'success') {
            this.message = text;
            this.messageType = type;
            setTimeout(() => this.message = null, 3000);
        },

        async saveCompany() {
            try {
                const res = await fetch('{{ route("customer.profile.update-company-address") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.company)
                });
                const data = await res.json();
                if (data.success) {
                    this.editSection = null;
                    this.showMessage('Firmenanschrift gespeichert');
                }
            } catch (e) {
                this.showMessage('Fehler beim Speichern', 'error');
            }
        },

        async saveBilling() {
            try {
                const res = await fetch('{{ route("customer.profile.update-billing-address") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.billing)
                });
                const data = await res.json();
                if (data.success) {
                    this.editSection = null;
                    this.showMessage('Rechnungsadresse gespeichert');
                }
            } catch (e) {
                this.showMessage('Fehler beim Speichern', 'error');
            }
        },

        departments: [],
        deptsLoaded: false,
        deptLoading: false,
        showDeptForm: false,
        deptEditId: null,
        deptForm: { name: '', description: '', code: '', is_active: true },

        async loadDepartments() {
            this.deptLoading = true;
            try {
                const res = await fetch('{{ route("customer.departments.index") }}', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.departments = data.departments || [];
                this.deptsLoaded = true;
            } catch (e) { console.error('Error:', e); }
            this.deptLoading = false;
        },

        async saveDepartment() {
            const url = this.deptEditId
                ? '{{ route("customer.departments.index") }}/' + this.deptEditId
                : '{{ route("customer.departments.store") }}';
            try {
                const res = await fetch(url, {
                    method: this.deptEditId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.deptForm)
                });
                if (res.ok) {
                    this.showDeptForm = false;
                    this.deptEditId = null;
                    this.loadDepartments();
                }
            } catch (e) { console.error('Error:', e); }
        },

        editDepartment(dept) {
            this.deptEditId = dept.id;
            this.deptForm = { name: dept.name, description: dept.description || '', code: dept.code || '', is_active: dept.is_active };
            this.showDeptForm = true;
        },

        deptDragId: null,
        async moveDepartment(id, dir) {
            const idx = this.departments.findIndex(d => d.id === id);
            const newIdx = idx + dir;
            if (newIdx < 0 || newIdx >= this.departments.length) return;
            [this.departments[idx], this.departments[newIdx]] = [this.departments[newIdx], this.departments[idx]];
            this.departments = [...this.departments];
            this.saveDeptOrder();
        },
        deptDragStart(id) { this.deptDragId = id; },
        deptDragOver(e) { e.preventDefault(); },
        async deptDrop(id) {
            if (this.deptDragId === null || this.deptDragId === id) { this.deptDragId = null; return; }
            const from = this.departments.findIndex(d => d.id === this.deptDragId);
            const to = this.departments.findIndex(d => d.id === id);
            const item = this.departments.splice(from, 1)[0];
            this.departments.splice(to, 0, item);
            this.departments = [...this.departments];
            this.deptDragId = null;
            this.saveDeptOrder();
        },
        async saveDeptOrder() {
            try { await fetch('{{ route("customer.departments.reorder") }}', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify({ ids: this.departments.map(d => d.id) }) }); } catch(e) {}
        },

        async deleteDepartment(id) {
            if (!confirm('Abteilung wirklich löschen?')) return;
            try {
                await fetch('{{ route("customer.departments.index") }}/' + id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.loadDepartments();
            } catch (e) { console.error('Error:', e); }
        }
    };
}

function usersManager() {
    return {
        usersTab: 'benutzer',
        employees: [],
        empLoading: false,
        showEmpForm: false,
        empEditId: null,
        empForm: { salutation: '', title: '', first_name: '', last_name: '', email: '', phone: '', mobile: '', position: '', department: '', department_id: '', personnel_number: '', branch_id: '', is_active: true, active_from: '', active_until: '', notes: '', group_ids: [] },
        availableBranches: [],
        availableDepartments: [],
        availableGroups: [],

        // Filter
        showFilter: true,
        filter: { search: '', status: '', group: '', department: '', branch: '' },

        get filteredEmployees() {
            return this.employees.filter(emp => {
                // Search
                if (this.filter.search) {
                    const q = this.filter.search.toLowerCase();
                    const haystack = [emp.first_name, emp.last_name, emp.email, emp.phone, emp.mobile, emp.position, emp.personnel_number].filter(Boolean).join(' ').toLowerCase();
                    if (!haystack.includes(q)) return false;
                }
                // Status
                const isActive = emp.is_currently_active !== undefined ? emp.is_currently_active : emp.is_active;
                if (this.filter.status === 'active' && !isActive) return false;
                if (this.filter.status === 'inactive' && isActive) return false;
                // Group
                if (this.filter.group === 'none' && emp.groups && emp.groups.length > 0) return false;
                if (this.filter.group && this.filter.group !== 'none') {
                    const gid = parseInt(this.filter.group);
                    if (!(emp.groups || []).some(g => g.id === gid)) return false;
                }
                // Department
                if (this.filter.department) {
                    if (String(emp.department_id) !== String(this.filter.department)) return false;
                }
                // Branch
                if (this.filter.branch) {
                    if (String(emp.branch_id) !== String(this.filter.branch)) return false;
                }
                return true;
            });
        },

        get hasActiveFilters() {
            return !!(this.filter.search || this.filter.status || this.filter.group || this.filter.department || this.filter.branch);
        },

        get activeFilterCount() {
            let c = 0;
            if (this.filter.search) c++;
            if (this.filter.status) c++;
            if (this.filter.group) c++;
            if (this.filter.department) c++;
            if (this.filter.branch) c++;
            return c;
        },

        resetFilters() {
            this.filter = { search: '', status: '', group: '', department: '', branch: '' };
        },

        // Groups
        groups: [],
        groupLoading: false,
        showGroupForm: false,
        groupEditId: null,
        groupForm: { name: '', description: '' },
        groupsLoaded: false,

        init() { this.loadEmployees(); },

        async loadEmployees() {
            this.empLoading = true;
            try {
                const [empRes, brRes, deptRes, grpRes] = await Promise.all([
                    fetch('{{ route("customer.employees.index") }}', { headers: { 'Accept': 'application/json' } }),
                    fetch('{{ route("customer.branches.index") }}', { headers: { 'Accept': 'application/json' } }),
                    fetch('{{ route("customer.departments.index") }}', { headers: { 'Accept': 'application/json' } }),
                    fetch('{{ route("customer.employee-groups.index") }}', { headers: { 'Accept': 'application/json' } })
                ]);
                const empData = await empRes.json();
                const brData = await brRes.json();
                const deptData = await deptRes.json();
                const grpData = await grpRes.json();
                this.employees = empData.employees || [];
                this.availableBranches = brData.branches || brData || [];
                this.availableDepartments = (deptData.departments || []).filter(d => d.is_active);
                this.availableGroups = grpData.groups || [];
            } catch (e) { console.error('Error:', e); }
            this.empLoading = false;
        },

        async saveEmployee() {
            const url = this.empEditId
                ? '{{ route("customer.employees.index") }}/' + this.empEditId
                : '{{ route("customer.employees.store") }}';
            try {
                const res = await fetch(url, {
                    method: this.empEditId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.empForm)
                });
                if (res.ok) {
                    this.showEmpForm = false;
                    this.empEditId = null;
                    this.loadEmployees();
                }
            } catch (e) { console.error('Error:', e); }
        },

        resetEmpForm() {
            this.empForm = { salutation: '', title: '', first_name: '', last_name: '', email: '', phone: '', mobile: '', position: '', department: '', department_id: '', personnel_number: '', branch_id: '', is_active: true, active_from: '', active_until: '', notes: '', group_ids: [] };
        },

        editEmployee(emp) {
            this.empEditId = emp.id;
            this.empForm = {
                salutation: emp.salutation || '', title: emp.title || '',
                first_name: emp.first_name, last_name: emp.last_name,
                email: emp.email || '', phone: emp.phone || '', mobile: emp.mobile || '',
                position: emp.position || '', department: emp.department || '',
                department_id: emp.department_id || '',
                personnel_number: emp.personnel_number || '',
                branch_id: emp.branch_id || '', is_active: emp.is_active,
                active_from: emp.active_from ? emp.active_from.substring(0, 10) : '',
                active_until: emp.active_until ? emp.active_until.substring(0, 10) : '',
                notes: emp.notes || '',
                group_ids: (emp.groups || []).map(g => g.id)
            };
            this.showEmpForm = true;
        },

        async deleteEmployee(id) {
            if (!confirm('Benutzer wirklich löschen?')) return;
            try {
                await fetch('{{ route("customer.employees.index") }}/' + id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.loadEmployees();
            } catch (e) { console.error('Error:', e); }
        },

        // --- Groups ---
        async loadGroups() {
            if (this.groupsLoaded) return;
            this.groupLoading = true;
            try {
                const res = await fetch('{{ route("customer.employee-groups.index") }}', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.groups = data.groups || [];
                this.groupsLoaded = true;
            } catch (e) { console.error('Error:', e); }
            this.groupLoading = false;
        },

        async saveGroup() {
            const url = this.groupEditId
                ? '{{ route("customer.employee-groups.index") }}/' + this.groupEditId
                : '{{ route("customer.employee-groups.store") }}';
            try {
                const res = await fetch(url, {
                    method: this.groupEditId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.groupForm)
                });
                if (res.ok) {
                    this.showGroupForm = false;
                    this.groupEditId = null;
                    this.groupsLoaded = false;
                    this.loadGroups();
                    this.loadEmployees();
                }
            } catch (e) { console.error('Error:', e); }
        },

        resetGroupForm() {
            this.groupForm = { name: '', description: '' };
        },

        editGroup(group) {
            this.groupEditId = group.id;
            this.groupForm = { name: group.name, description: group.description || '' };
            this.showGroupForm = true;
        },

        async deleteGroup(id) {
            if (!confirm('Gruppe wirklich löschen? Die Zuordnungen zu Benutzern werden ebenfalls entfernt.')) return;
            try {
                await fetch('{{ route("customer.employee-groups.index") }}/' + id, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.groupsLoaded = false;
                this.loadGroups();
                this.loadEmployees();
            } catch (e) { console.error('Error:', e); }
        }
    };
}

function assignedAgencies() {
    return {
        agencies: [],
        search: '',
        page: 1,
        lastPage: 1,
        total: 0,
        loading: false,
        detailAgency: null,
        sortField: 'company_name',
        sortDir: 'asc',
        showInactive: false,

        isInactive(agency) {
            const today = new Date().toISOString().slice(0, 10);
            return (agency.legacy_options?.live_from && today < agency.legacy_options.live_from) ||
                   (agency.legacy_options?.end_of_use && today > agency.legacy_options.end_of_use);
        },

        get filteredAgencies() {
            if (this.showInactive) return this.agencies;
            return this.agencies.filter(a => !this.isInactive(a));
        },

        init() {
            this.loadAgencies();
        },

        async loadAgencies() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.page, search: this.search });
                const res = await fetch(`/customer/assigned-agencies?${params}`);
                const data = await res.json();
                this.agencies = data.data;
                this.lastPage = data.last_page;
                this.total = data.total;
                this.sortAgencies();
            } catch (e) {
                console.error('Error loading agencies:', e);
            }
            this.loading = false;
        },

        toggleSort(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = 'asc';
            }
            this.sortAgencies();
        },

        sortAgencies() {
            const field = this.sortField;
            const dir = this.sortDir === 'asc' ? 1 : -1;
            this.agencies.sort((a, b) => {
                let valA = field === 'legacy_client_account_id' ? (a[field] ?? 0) : (a[field] || '').toString().toLowerCase();
                let valB = field === 'legacy_client_account_id' ? (b[field] ?? 0) : (b[field] || '').toString().toLowerCase();
                if (valA < valB) return -1 * dir;
                if (valA > valB) return 1 * dir;
                return 0;
            });
        },

        nextPage() {
            if (this.page < this.lastPage) { this.page++; this.loadAgencies(); }
        },

        prevPage() {
            if (this.page > 1) { this.page--; this.loadAgencies(); }
        },

        showAgencyDetail(agency) {
            this.detailAgency = agency;
        }
    };
}

function organizationManager() {
    return {
        orgTab: 'uebersicht',
        branches: [],
        orgNodes: [],
        loading: true,
        showNewForm: false,
        branchTab: 'adresse',
        branchPhones: [],
        branchEmails: [],
        branchWebs: [],
        branchContacts: [],
        newForm: { editId: null, name: '', additional: '', street: '', house_number: '', postal_code: '', city: '', country: 'Deutschland', org_node_ids: [], org_node_data: [] },

        init() { this.loadAll(); },

        async loadAll() {
            this.loading = true;
            try {
                const [br, on] = await Promise.all([
                    fetch('{{ route("customer.branches.index") }}', { headers: { 'Accept': 'application/json' } }),
                    fetch('{{ route("customer.org-nodes.index") }}', { headers: { 'Accept': 'application/json' } })
                ]);
                const brData = await br.json();
                const onData = await on.json();
                this.branches = brData.branches || brData || [];
                this.orgNodes = onData.nodes || [];
            } catch (e) { console.error(e); }
            this.loading = false;
        },

        resetNewForm() {
            this.newForm = { editId: null, name: '', additional: '', street: '', house_number: '', postal_code: '', city: '', country: 'Deutschland', org_node_ids: [], org_node_data: [] };
            this.branchPhones = [];
            this.branchEmails = [];
            this.branchWebs = [];
            this.branchContacts = [];
            this.branchTab = 'adresse';
        },

        async saveNewBranch() {
            const url = this.newForm.editId
                ? '{{ route("customer.branches.index") }}/' + this.newForm.editId
                : '{{ route("customer.branches.store") }}';
            try {
                const res = await fetch(url, {
                    method: this.newForm.editId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.newForm)
                });
                if (res.ok) {
                    const data = await res.json();
                    const branchId = data.branch?.id || this.newForm.editId;
                    if (branchId) {
                        await this.saveBranchContacts(branchId);
                    }
                    this.showNewForm = false; this.resetNewForm(); this.loadAll();
                }
            } catch (e) { console.error(e); }
        },

        async saveBranchContacts(branchId) {
            const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' };

            // Delete existing contacts for this branch, then recreate
            // Phones
            for (const p of this.branchPhones) {
                if (p.id) {
                    await fetch('{{ route("customer.phone-numbers.index") }}/' + p.id, { method: 'PUT', headers, body: JSON.stringify({ ...p, branch_id: branchId }) });
                } else if (p.label && p.number) {
                    await fetch('{{ route("customer.phone-numbers.store") }}', { method: 'POST', headers, body: JSON.stringify({ ...p, branch_id: branchId }) });
                }
            }
            // Emails
            for (const e of this.branchEmails) {
                if (e.id) {
                    await fetch('{{ route("customer.email-addresses.index") }}/' + e.id, { method: 'PUT', headers, body: JSON.stringify({ ...e, branch_id: branchId }) });
                } else if (e.label && e.email) {
                    await fetch('{{ route("customer.email-addresses.store") }}', { method: 'POST', headers, body: JSON.stringify({ ...e, branch_id: branchId }) });
                }
            }
            // Websites
            for (const w of this.branchWebs) {
                if (w.id) {
                    await fetch('{{ route("customer.websites.index") }}/' + w.id, { method: 'PUT', headers, body: JSON.stringify({ ...w, branch_id: branchId }) });
                } else if (w.label && w.url) {
                    await fetch('{{ route("customer.websites.store") }}', { method: 'POST', headers, body: JSON.stringify({ ...w, branch_id: branchId }) });
                }
            }
            // Contacts
            for (const c of this.branchContacts) {
                if (c.id) {
                    await fetch('/customer/branch-contacts/' + c.id, { method: 'PUT', headers, body: JSON.stringify(c) });
                } else if (c.first_name || c.last_name) {
                    await fetch('/customer/branch-contacts', { method: 'POST', headers, body: JSON.stringify({ ...c, branch_id: branchId }) });
                }
            }
        },

        editExistingBranch(branch) {
            this.newForm = {
                editId: branch.id,
                name: branch.name,
                additional: branch.additional || '',
                street: branch.street,
                house_number: branch.house_number || '',
                postal_code: branch.postal_code,
                city: branch.city,
                country: branch.country || 'Deutschland',
                org_node_ids: (branch.org_nodes || []).map(n => n.id),
                org_node_data: (branch.org_nodes || []).map(n => ({
                    id: n.id,
                    customer_number: n.pivot?.customer_number || '',
                    contract_number: n.pivot?.contract_number || '',
                    start_date: n.pivot?.start_date || '',
                    end_date: n.pivot?.end_date || ''
                }))
            };
            this.branchPhones = (branch.phone_numbers || []).map(p => ({id: p.id, label: p.label, number: p.number, type: p.type, notes: p.notes || ''}));
            this.branchEmails = (branch.email_addresses || []).map(e => ({id: e.id, label: e.label, email: e.email, notes: e.notes || ''}));
            this.branchWebs = (branch.websites || []).map(w => ({id: w.id, label: w.label, url: w.url, notes: w.notes || ''}));
            this.branchContacts = (branch.contacts || []).map(c => ({id: c.id, salutation: c.salutation || '', title: c.title || '', first_name: c.first_name || '', last_name: c.last_name || '', function: c.function || '', department: c.department || '', phone: c.phone || '', mobile: c.mobile || '', fax: c.fax || '', email: c.email || '', notes: c.notes || ''}));
            this.branchTab = 'adresse';
            this.showNewForm = true;
        },

        async deleteBranch(id) {
            if (!confirm('Standort wirklich löschen?')) return;
            try {
                await fetch('{{ route("customer.branches.index") }}/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                this.loadAll();
            } catch (e) { console.error(e); }
        },

        toggleOrgNode(id) {
            const isSelected = this.newForm.org_node_ids.includes(id);
            if (isSelected) {
                const removeIds = this.collectChildIds(id, this.orgNodes);
                removeIds.push(id);
                this.newForm.org_node_ids = this.newForm.org_node_ids.filter(i => !removeIds.includes(i));
                this.newForm.org_node_data = this.newForm.org_node_data.filter(d => !removeIds.includes(d.id));
            } else {
                this.newForm.org_node_ids.push(id);
                if (!this.newForm.org_node_data.find(d => d.id === id)) {
                    this.newForm.org_node_data.push({ id, customer_number: '', contract_number: '', start_date: '', end_date: '' });
                }
                const parentIds = this.collectParentIds(id, this.orgNodes);
                parentIds.forEach(pid => {
                    if (!this.newForm.org_node_ids.includes(pid)) {
                        this.newForm.org_node_ids.push(pid);
                        if (!this.newForm.org_node_data.find(d => d.id === pid)) {
                            this.newForm.org_node_data.push({ id: pid, customer_number: '', contract_number: '' });
                        }
                    }
                });
            }
        },

        getNodeData(nodeId) {
            return this.newForm.org_node_data.find(d => d.id === nodeId) || { id: nodeId, customer_number: '', contract_number: '', start_date: '', end_date: '' };
        },

        updateNodeData(nodeId, field, value) {
            let entry = this.newForm.org_node_data.find(d => d.id === nodeId);
            if (!entry) {
                entry = { id: nodeId, customer_number: '', contract_number: '', start_date: '', end_date: '' };
                this.newForm.org_node_data.push(entry);
            }
            entry[field] = value;
        },

        collectChildIds(id, nodes) {
            let ids = [];
            for (const n of nodes) {
                if (n.id === id) {
                    const children = n.all_children || [];
                    children.forEach(c => {
                        ids.push(c.id);
                        ids = ids.concat(this.collectChildIds(c.id, [c]));
                    });
                    return ids;
                }
                const children = n.all_children || [];
                if (children.length) {
                    const found = this.collectChildIds(id, children);
                    if (found.length) return found;
                }
            }
            return ids;
        },

        collectParentIds(id, nodes, path = []) {
            for (const n of nodes) {
                if (n.id === id) return path;
                const children = n.all_children || [];
                if (children.length) {
                    const found = this.collectParentIds(id, children, [...path, n.id]);
                    if (found !== null) return found;
                }
            }
            return null;
        },

        renderOrgCheckbox(node, depth) {
            const checked = this.newForm.org_node_ids.includes(node.id);
            const children = node.all_children || [];
            const pad = depth * 20;
            const esc = (s) => s ? s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : '';

            let html = '<label class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-white cursor-pointer transition-colors" style="padding-left:' + (pad + 8) + 'px">';
            html += '<input type="checkbox" ' + (checked ? 'checked' : '') + ' onchange="window.dispatchEvent(new CustomEvent(\'org-toggle-node\',{detail:{id:' + node.id + '}}))" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">';
            html += '<span class="w-2 h-2 rounded-full flex-shrink-0" style="background:' + (node.color || '#3b82f6') + '"></span>';
            html += '<span class="text-xs text-gray-700">' + esc(node.name) + '</span>';
            if (node.code) html += '<span class="text-[10px] text-gray-400 font-mono">' + esc(node.code) + '</span>';
            html += '</label>';

            children.forEach(child => {
                html += this.renderOrgCheckbox(child, depth + 1);
            });

            return html;
        },

        renderOrgCheckboxWithFields() {
            let html = '';
            this.orgNodes.forEach(node => {
                html += this._renderOrgNode(node);
            });
            return html;
        },

        _renderOrgNode(node) {
            const checked = this.newForm.org_node_ids.includes(node.id);
            const children = node.all_children || [];
            const esc = (s) => s ? s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : '';
            const nid = node.id;
            const nd = this.getNodeData(nid);

            let html = '<div class="org-tree-node">';

            // Karte
            html += '<div class="org-tree-node-row">';
            html += '<div class="org-tree-card ' + (checked ? 'checked' : '') + '">';
            html += '<div class="flex items-center gap-2 px-3 py-2">';
            html += '<input type="checkbox" ' + (checked ? 'checked' : '') + ' onchange="window.dispatchEvent(new CustomEvent(\'org-toggle-node\',{detail:{id:' + nid + '}}))" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5 flex-shrink-0">';
            html += '<span class="org-tree-dot" style="background:' + (node.color || '#3b82f6') + '"></span>';
            html += '<span class="text-xs font-medium text-gray-800 flex-1">' + esc(node.name) + '</span>';
            if (node.code) html += '<span class="text-[10px] text-gray-400 font-mono">' + esc(node.code) + '</span>';
            html += '</div>';

            if (checked) {
                html += '<div class="grid grid-cols-2 lg:grid-cols-4 gap-2 px-3 pb-2 ml-7">';
                html += '<div><label class="block text-[9px] text-gray-400 mb-0.5">Kundennummer</label><input type="text" value="' + esc(nd.customer_number) + '" placeholder="Kundennr." onchange="window.dispatchEvent(new CustomEvent(\'org-update-data\',{detail:{id:' + nid + ',field:\'customer_number\',value:this.value}}))" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-blue-500 bg-white"></div>';
                html += '<div><label class="block text-[9px] text-gray-400 mb-0.5">Vertragsnummer</label><input type="text" value="' + esc(nd.contract_number) + '" placeholder="Vertragsnr." onchange="window.dispatchEvent(new CustomEvent(\'org-update-data\',{detail:{id:' + nid + ',field:\'contract_number\',value:this.value}}))" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-blue-500 bg-white"></div>';
                html += '<div><label class="block text-[9px] text-gray-400 mb-0.5">Start</label><input type="date" value="' + esc(nd.start_date) + '" onchange="window.dispatchEvent(new CustomEvent(\'org-update-data\',{detail:{id:' + nid + ',field:\'start_date\',value:this.value}}))" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-blue-500 bg-white"></div>';
                html += '<div><label class="block text-[9px] text-gray-400 mb-0.5">Ende</label><input type="date" value="' + esc(nd.end_date) + '" onchange="window.dispatchEvent(new CustomEvent(\'org-update-data\',{detail:{id:' + nid + ',field:\'end_date\',value:this.value}}))" class="w-full px-2 py-1 border border-gray-300 rounded text-[11px] focus:ring-1 focus:ring-blue-500 bg-white"></div>';
                html += '</div>';
            }
            html += '</div>'; // card
            html += '</div>'; // node-row

            // Kinder verschachtelt
            if (children.length) {
                html += '<div class="org-tree-branch">';
                children.forEach(child => {
                    html += this._renderOrgNode(child);
                });
                html += '</div>';
            }

            html += '</div>'; // node
            return html;
        }
    };
}
</script>
@endpush
