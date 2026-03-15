@extends('layouts.dashboard-minimal')

@section('title', 'Einstellungen - Global Travel Monitor')

@php
    $active = 'customer-settings';
    $customer = auth('customer')->user();
    $featureService = app(\App\Services\CustomerFeatureService::class);
    $settingsSection = request()->query('section', 'general');
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
    {{-- Sidebar --}}
    <div class="settings-sidebar">
        <div class="p-4">
            <h2 class="text-sm font-bold text-gray-900 mb-3">
                <i class="fas fa-cog mr-2"></i>
                Einstellungen
            </h2>

            <nav class="space-y-1">
                <div class="settings-section-title">Allgemein</div>

                <a href="{{ route('customer.settings', ['section' => 'general']) }}"
                   class="settings-nav-item {{ $settingsSection === 'general' ? 'active' : '' }}">
                    <i class="fas fa-user"></i>
                    Mein Profil
                </a>

                <a href="{{ route('customer.settings', ['section' => 'notifications']) }}"
                   class="settings-nav-item {{ $settingsSection === 'notifications' ? 'active' : '' }}">
                    <i class="fas fa-bell"></i>
                    Benachrichtigungen
                </a>

                @if($customer->gtm_api_enabled)
                <a href="{{ route('customer.settings', ['section' => 'api']) }}"
                   class="settings-nav-item {{ $settingsSection === 'api' ? 'active' : '' }}">
                    <i class="fas fa-code"></i>
                    API-Zugang
                </a>
                @endif

                @if($customer->branch_management_active)
                <div class="settings-section-title mt-2">Organisation</div>

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
            </nav>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="settings-content">
        <div class="p-6" x-data="settingsManager()">
            @if($settingsSection === 'general')
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
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
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
                            <p class="font-medium text-gray-900">{{ $customer->name }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">E-Mail</span>
                            <p class="font-medium text-gray-900">{{ $customer->email }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Telefon</span>
                            <p class="font-medium text-gray-900">{{ $customer->phone ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Kundentyp</span>
                            <p class="font-medium text-gray-900">
                                @if($customer->customer_type === 'business') Firmenkunde
                                @elseif($customer->customer_type === 'private') Privatkunde
                                @else — @endif
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Registriert am</span>
                            <p class="font-medium text-gray-900">{{ $customer->created_at?->format('d.m.Y') }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500">Login via</span>
                            <p class="font-medium text-gray-900">{{ $customer->provider ?: 'E-Mail' }}</p>
                        </div>
                    </div>

                    {{-- Edit Mode --}}
                    <form x-show="editSection === 'personal'" x-cloak @submit.prevent="savePersonal" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                            <input type="text" x-model="personal.name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">E-Mail <span class="text-red-500">*</span></label>
                            <input type="email" x-model="personal.email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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

                {{-- Passwort ändern --}}
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

                {{-- Zwei-Faktor-Authentifizierung --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5" x-data="twoFactorManager()">
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
                                const r = await fetch('{{ route('customer.notification-settings.templates.index') }}', { headers: { 'Accept': 'application/json' } });
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
                                    @livewire('customer.notification-template-form', [], key('settings-tpl-form'))
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
                                const r = await fetch('{{ route('customer.notification-settings.rules.json') }}', { headers: { 'Accept': 'application/json' } });
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
                                    @livewire('customer.notification-rule-form', [], key('settings-rule-form'))
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
                                const r = await fetch('{{ route('customer.notification-settings.logs') }}?page=' + this.logsPage, { headers: { 'Accept': 'application/json' } });
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

            @elseif($settingsSection === 'api')
                <h3 class="text-lg font-semibold text-gray-900 mb-1">API-Zugang</h3>
                <p class="text-sm text-gray-500 mb-6">Verwalten Sie Ihren API-Token für den Zugriff auf die GTM-API.</p>

                <div class="bg-white rounded-lg border border-gray-200 p-5" x-data="{
                    generatedToken: '', hasToken: false, loading: false, copied: false,
                    async init() { await this.checkStatus(); },
                    async checkStatus() {
                        try {
                            const r = await fetch('{{ route('customer.api-tokens.status') }}', { headers: { 'Accept': 'application/json' } });
                            const d = await r.json();
                            this.hasToken = d.has_token || false;
                        } catch(e) {}
                    },
                    async generateToken() {
                        this.loading = true;
                        try {
                            const r = await fetch('{{ route('customer.api-tokens.generate') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                            const d = await r.json();
                            if (d.token) { this.generatedToken = d.token; this.hasToken = true; }
                        } catch(e) {}
                        this.loading = false;
                    },
                    async revokeToken() {
                        if (!confirm('Möchten Sie den API-Token wirklich widerrufen?')) return;
                        this.loading = true;
                        try {
                            await fetch('{{ route('customer.api-tokens.revoke') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                            this.hasToken = false; this.generatedToken = '';
                        } catch(e) {}
                        this.loading = false;
                    },
                    copyToken() {
                        navigator.clipboard.writeText(this.generatedToken);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }
                }">
                    {{-- Token anzeigen --}}
                    <div x-show="generatedToken" x-cloak class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800 font-medium mb-2"><i class="fas fa-check-circle mr-1"></i> API Token erfolgreich generiert</p>
                        <div class="flex gap-2 items-center">
                            <input type="text" x-model="generatedToken" readonly class="flex-1 px-3 py-2 bg-white border border-green-300 rounded-lg text-sm font-mono select-all" @click="$el.select()">
                            <button @click="copyToken" class="px-4 py-2 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 flex items-center gap-1">
                                <i class="fas fa-copy"></i> <span x-text="copied ? 'Kopiert!' : 'Kopieren'"></span>
                            </button>
                        </div>
                        <p class="text-xs text-green-700 mt-2"><i class="fas fa-info-circle mr-1"></i>Bitte speichern Sie diesen Token sicher. Er wird nur einmal angezeigt.</p>
                    </div>

                    {{-- Kein Token --}}
                    <div x-show="!generatedToken && !hasToken" x-cloak class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs text-blue-800"><i class="fas fa-info-circle mr-1"></i>Sie haben noch keinen API-Token. Generieren Sie einen Token, um auf die API zugreifen zu können.</p>
                    </div>

                    {{-- Token vorhanden --}}
                    <div x-show="!generatedToken && hasToken" x-cloak class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs text-yellow-800"><i class="fas fa-shield-halved mr-1"></i>Sie haben bereits einen aktiven API-Token. Das Generieren eines neuen Tokens widerruft automatisch den alten Token.</p>
                    </div>

                    {{-- Aktionen --}}
                    <div class="flex gap-3">
                        <button @click="generateToken" :disabled="loading"
                            :class="loading ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                            class="px-4 py-2 text-white text-xs rounded-lg flex items-center gap-1">
                            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                            <span x-text="loading ? 'Wird generiert...' : (hasToken ? 'Neuen Token generieren' : 'Token generieren')"></span>
                        </button>
                        <button x-show="hasToken && !generatedToken" @click="revokeToken" :disabled="loading"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg flex items-center gap-1">
                            <i class="fas fa-trash"></i> Token widerrufen
                        </button>
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
                        <div class="bg-white rounded-lg border border-gray-200 p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $customer->company_country ?: '—' }}</p>
                            <p class="text-xs text-gray-500 mt-1">Land</p>
                        </div>
                    </div>

                    {{-- Kundentyp --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mb-5" x-data="customerTypeManager()">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Kundentyp</h4>
                        <p class="text-xs text-gray-500 mb-4">Bitte wählen Sie aus, ob Sie Firmenkunde oder Privatkunde sind.</p>
                        <div class="flex gap-3 mb-4">
                            <button @click="updateCustomerType('business')"
                                :class="customerType === 'business' ? 'bg-blue-50 text-blue-700 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors flex items-center gap-2">
                                <i class="fas fa-building text-xs"></i> Firmenkunde
                            </button>
                            <button @click="updateCustomerType('private')"
                                :class="customerType === 'private' ? 'bg-blue-50 text-blue-700 border-blue-300' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors flex items-center gap-2">
                                <i class="fas fa-user text-xs"></i> Privatkunde
                            </button>
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
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Benutzer</h3>
                <p class="text-sm text-gray-500 mb-4">Verwalten Sie die Benutzer Ihrer Organisation.</p>

                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs text-gray-500"><span x-text="employees.length"></span> Benutzer erfasst</p>
                        <button @click="showEmpForm = true; empEditId = null; resetEmpForm();"
                                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs">
                            <i class="fas fa-plus"></i> Neuer Benutzer
                        </button>
                    </div>

                    {{-- Add/Edit Form --}}
                    <div x-show="showEmpForm" x-cloak class="bg-white rounded-lg border border-gray-200 mb-5 overflow-hidden">
                        <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                            <h4 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                <i class="fas" :class="empEditId ? 'fa-pen' : 'fa-user-plus'" class="text-blue-600"></i>
                                <span x-text="empEditId ? 'Benutzer bearbeiten' : 'Neuen Benutzer erfassen'"></span>
                            </h4>
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

                            {{-- Sektion: Notiz --}}
                            <div class="mb-5">
                                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <i class="fas fa-sticky-note text-gray-400"></i> Notiz
                                </h5>
                                <textarea x-model="empForm.notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Interne Anmerkungen zu diesem Benutzer..."></textarea>
                            </div>

                            {{-- Status + Buttons --}}
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" x-model="empForm.is_active" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700">Benutzer ist aktiv</span>
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" @click="showEmpForm = false" class="px-4 py-2 text-xs text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Abbrechen</button>
                                    <button type="submit" class="px-4 py-2 text-xs text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1">
                                        <i class="fas fa-save"></i> <span x-text="empEditId ? 'Aktualisieren' : 'Speichern'"></span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

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

                    {{-- User List --}}
                    <div x-show="!empLoading && employees.length > 0" class="space-y-3">
                        <template x-for="emp in employees" :key="emp.id">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <h4 class="text-sm font-semibold text-gray-900">
                                                <span x-show="emp.salutation" x-text="emp.salutation === 'herr' ? 'Herr' : emp.salutation === 'frau' ? 'Frau' : ''"></span>
                                                <span x-show="emp.title" x-text="emp.title"></span>
                                                <span x-text="emp.first_name + ' ' + emp.last_name"></span>
                                            </h4>
                                            <span x-show="!emp.is_active" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">Inaktiv</span>
                                            <span x-show="emp.position" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800" x-text="emp.position"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                            <span x-show="emp.department_relation || emp.department"><i class="fas fa-folder mr-1"></i><span x-text="emp.department_relation ? emp.department_relation.name : emp.department"></span></span>
                                            <span x-show="emp.email"><i class="fas fa-envelope mr-1"></i><span x-text="emp.email"></span></span>
                                            <span x-show="emp.phone"><i class="fas fa-phone mr-1"></i><span x-text="emp.phone"></span></span>
                                            <span x-show="emp.mobile"><i class="fas fa-mobile-screen mr-1"></i><span x-text="emp.mobile"></span></span>
                                            <span x-show="emp.branch"><i class="fas fa-building mr-1"></i><span x-text="emp.branch?.name"></span></span>
                                            <span x-show="emp.personnel_number"><i class="fas fa-id-badge mr-1"></i><span x-text="emp.personnel_number"></span></span>
                                        </div>
                                        <p x-show="emp.notes" class="text-xs text-gray-400 mt-1 italic line-clamp-1" x-text="emp.notes"></p>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button @click="editEmployee(emp)" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 transition-colors" title="Bearbeiten">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <button @click="deleteEmployee(emp.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 transition-colors" title="Löschen">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
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
                </div>

                {{-- ==================== Tab: Übersicht ==================== --}}
                <div x-show="orgTab === 'uebersicht'">
                    @php
                        $branchCount = \App\Models\Branch::where('customer_id', $customer->id)->count();
                        $employeeCount = \App\Models\Employee::where('customer_id', $customer->id)->count();
                        $deptCount = \App\Models\Department::where('customer_id', $customer->id)->count();
                    @endphp

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
                            <div class="bg-white rounded-lg border border-gray-200 p-3 text-center">
                                <p class="text-lg font-bold text-gray-900 truncate">{{ $customer->company_city ?: '—' }}</p>
                                <p class="text-[10px] text-gray-500">Hauptsitz</p>
                            </div>
                        </div>
                        <button @click="showNewForm = true" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-xs flex-shrink-0">
                            <i class="fas fa-plus"></i> Neue Adresse
                        </button>
                    </div>

                    @include('customer.settings.partials.branch-form')

                    {{-- Adressen-Liste --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-gray-900">Adressen</h4>
                        </div>
                        <div x-show="loading" class="text-center py-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>
                        <div x-show="!loading" class="space-y-2">
                            <template x-if="branches.length === 0 && !showNewForm">
                                <div class="text-center py-4">
                                    <i class="fas fa-building text-2xl text-gray-300 mb-2"></i>
                                    <p class="text-xs text-gray-500">Noch keine Adressen angelegt.</p>
                                </div>
                            </template>
                            <template x-for="branch in branches" :key="branch.id">
                                <div class="flex items-center gap-3 py-2.5 border-b border-gray-100 last:border-0 group">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-building text-xs text-gray-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-xs font-medium text-gray-900 truncate" x-text="branch.name"></p>
                                            <span x-show="branch.is_headquarters" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800 flex-shrink-0">HQ</span>
                                        </div>
                                        <p class="text-[10px] text-gray-500" x-text="(branch.street || '') + ' ' + (branch.house_number || '') + ', ' + (branch.postal_code || '') + ' ' + (branch.city || '')"></p>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <template x-for="on in (branch.org_nodes || [])" :key="on.id">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700" x-text="on.name"></span>
                                            </template>
                                            <span x-show="(branch.phone_numbers || []).length" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-phone mr-0.5 text-[8px]"></i> <span x-text="branch.phone_numbers.length"></span>
                                            </span>
                                            <span x-show="(branch.email_addresses || []).length" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-envelope mr-0.5 text-[8px]"></i> <span x-text="branch.email_addresses.length"></span>
                                            </span>
                                            <span x-show="(branch.websites || []).length" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-globe mr-0.5 text-[8px]"></i> <span x-text="branch.websites.length"></span>
                                            </span>
                                            <span x-show="(branch.contacts || []).length" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-address-card mr-0.5 text-[8px]"></i> <span x-text="branch.contacts.length"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="relative flex-shrink-0" x-data="{ open: false }">
                                        <button @click="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                            <i class="fas fa-ellipsis-vertical text-sm"></i>
                                        </button>
                                        <div x-show="open" @click.away="open = false" x-transition x-cloak class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <button @click="editExistingBranch(branch); open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                                <i class="fas fa-pen w-4 text-center text-blue-500"></i> Bearbeiten
                                            </button>
                                            <button x-show="!branch.is_headquarters" @click="deleteBranch(branch.id); open = false" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                <i class="fas fa-trash w-4 text-center"></i> Löschen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ==================== Tab: Struktur ==================== --}}
                <div x-show="orgTab === 'struktur'" x-cloak>
                    @include('customer.settings.partials.tab-org-chart')
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
            name: @json($customer->name),
            email: @json($customer->email),
            phone: @json($customer->phone ?? ''),
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
        customerType: @json($customer->customer_type ?? ''),
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

        async confirmPassword() {
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
        employees: [],
        empLoading: false,
        showEmpForm: false,
        empEditId: null,
        empForm: { salutation: '', title: '', first_name: '', last_name: '', email: '', phone: '', mobile: '', position: '', department: '', department_id: '', personnel_number: '', branch_id: '', is_active: true, notes: '' },
        availableBranches: [],
        availableDepartments: [],

        init() { this.loadEmployees(); },

        async loadEmployees() {
            this.empLoading = true;
            try {
                const [empRes, brRes, deptRes] = await Promise.all([
                    fetch('{{ route("customer.employees.index") }}', { headers: { 'Accept': 'application/json' } }),
                    fetch('{{ route("customer.branches.index") }}', { headers: { 'Accept': 'application/json' } }),
                    fetch('{{ route("customer.departments.index") }}', { headers: { 'Accept': 'application/json' } })
                ]);
                const empData = await empRes.json();
                const brData = await brRes.json();
                const deptData = await deptRes.json();
                this.employees = empData.employees || [];
                this.availableBranches = brData.branches || brData || [];
                this.availableDepartments = (deptData.departments || []).filter(d => d.is_active);
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
            this.empForm = { salutation: '', title: '', first_name: '', last_name: '', email: '', phone: '', mobile: '', position: '', department: '', department_id: '', personnel_number: '', branch_id: '', is_active: true, notes: '' };
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
                notes: emp.notes || ''
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
