<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Business Visum - Global Travel Monitor</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('android-chrome-192x192.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Einbindung -->
    @php($faKit = config('services.fontawesome.kit'))
    @if(!empty($faKit))
        <script src="https://kit.fontawesome.com/{{ e($faKit) }}.js" crossorigin="anonymous" onload="window.__faKitOk=true" onerror="window.__faKitOk=false"></script>
        <script>
        (function(){
            function addCss(href){
                var l=document.createElement('link'); l.rel='stylesheet'; l.href=href; document.head.appendChild(l);
            }
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){ if(!window.__faKitOk){ addCss(fallbackHref); } }, 800);
            });
        })();
        </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    @endif
    <style>
        /* Basis-Layout */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Header - feststehend */
        .header {
            flex-shrink: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        /* Footer - feststehend */
        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        /* Hauptbereich - dynamisch */
        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }

        /* Navigation - feste Breite */
        .navigation {
            flex-shrink: 0;
            width: 64px;
            background: black;
        }

        /* Content Area */
        .content-area {
            flex: 1;
            overflow-y: auto;
            background: #f3f4f6;
        }

        /* Custom Select Styling */
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Loading Spinner */
        .loading-spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <x-public-header />

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Black Navigation Sidebar -->
            <x-public-navigation active="business-visa" />

            <!-- Content Area -->
            <div class="content-area" x-data="businessVisaForm()">
                <div class="max-w-4xl mx-auto py-8 px-4">
                    <!-- Page Header -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fa-regular fa-id-card text-blue-600 text-2xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Business Visum Check</h1>
                                <p class="text-gray-600">Prüfen Sie die Visumbestimmungen für Ihre Geschäftsreise</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <form @submit.prevent="submitForm">
                            <!-- Nationality Section -->
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fa-regular fa-passport text-blue-500"></i>
                                    Staatsangehörigkeit
                                </h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="nationality" class="block text-sm font-medium text-gray-700 mb-1">
                                            Staatsangehörigkeit <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="nationality"
                                            x-model="formData.nationality"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                                            required
                                        >
                                            <option value="">Bitte wählen...</option>
                                            @foreach($countries as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="secondNationality" class="block text-sm font-medium text-gray-700 mb-1">
                                            Zweite Staatsangehörigkeit (optional)
                                        </label>
                                        <select
                                            id="secondNationality"
                                            x-model="formData.secondNationality"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                                        >
                                            <option value="">Keine</option>
                                            @foreach($countries as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Countries Section -->
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fa-regular fa-globe text-blue-500"></i>
                                    Länder
                                </h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="residenceCountry" class="block text-sm font-medium text-gray-700 mb-1">
                                            Wohnsitzland <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="residenceCountry"
                                            x-model="formData.residenceCountry"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                                            required
                                        >
                                            <option value="">Bitte wählen...</option>
                                            @foreach($countries as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="destinationCountry" class="block text-sm font-medium text-gray-700 mb-1">
                                            Zielland <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="destinationCountry"
                                            x-model="formData.destinationCountry"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                                            required
                                        >
                                            <option value="">Bitte wählen...</option>
                                            @foreach($countries as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Trip Details Section -->
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fa-regular fa-calendar text-blue-500"></i>
                                    Reisedaten
                                </h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="tripStartDate" class="block text-sm font-medium text-gray-700 mb-1">
                                            Reisebeginn <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            id="tripStartDate"
                                            x-model="formData.tripStartDate"
                                            :min="today"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label for="tripEndDate" class="block text-sm font-medium text-gray-700 mb-1">
                                            Reiseende <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            id="tripEndDate"
                                            x-model="formData.tripEndDate"
                                            :min="formData.tripStartDate || today"
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                                            required
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Trip Reason Section -->
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fa-regular fa-briefcase text-blue-500"></i>
                                    Reisegrund <span class="text-red-500">*</span>
                                </h2>
                                <div class="space-y-4">
                                    @foreach($groupedReasons as $group => $reasons)
                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                                <h3 class="text-sm font-semibold text-gray-700">{{ $group }}</h3>
                                            </div>
                                            <div class="p-4 space-y-2">
                                                @foreach($reasons as $value => $reason)
                                                    <label class="flex items-start gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-colors">
                                                        <input
                                                            type="radio"
                                                            name="tripReason"
                                                            value="{{ $value }}"
                                                            x-model="formData.tripReason"
                                                            class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                                        >
                                                        <span class="text-sm text-gray-700">{{ $reason['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div x-show="error" x-cloak class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center gap-2 text-red-700">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <span x-text="error"></span>
                                </div>
                            </div>

                            <!-- Debug Log -->
                            <div x-show="debugLog && debugLog.length > 0" x-cloak class="mb-6">
                                <details class="border border-gray-300 rounded-lg bg-gray-900">
                                    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-gray-200 hover:bg-gray-800">
                                        <i class="fa-solid fa-bug mr-2"></i> Debug Log (<span x-text="debugLog.length"></span> Einträge)
                                    </summary>
                                    <div class="px-4 py-3 border-t border-gray-700 max-h-96 overflow-y-auto">
                                        <template x-for="(entry, index) in debugLog" :key="index">
                                            <div class="mb-3 pb-3 border-b border-gray-700 last:border-0">
                                                <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
                                                    <span class="font-mono" x-text="entry.time"></span>
                                                    <span class="font-semibold text-green-400" x-text="entry.message"></span>
                                                </div>
                                                <pre class="text-xs text-gray-300 overflow-x-auto whitespace-pre-wrap font-mono" x-text="JSON.stringify(entry.context, null, 2)"></pre>
                                            </div>
                                        </template>
                                    </div>
                                </details>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold rounded-lg transition-colors shadow-sm"
                                >
                                    <template x-if="loading">
                                        <div class="loading-spinner"></div>
                                    </template>
                                    <template x-if="!loading">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </template>
                                    <span x-text="loading ? 'Prüfung läuft...' : 'Visumbestimmungen prüfen'"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Results Section -->
                    <div x-show="result" x-cloak class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fa-regular fa-clipboard-check text-green-500"></i>
                            Ergebnis der Visum-Prüfung
                        </h2>

                        <template x-if="result">
                            <div class="space-y-4">
                                <!-- Visa Required Status -->
                                <div class="p-4 rounded-lg" :class="result.visaQualification === 'VISA_REQUIRED' ? 'bg-amber-50 border border-amber-200' : 'bg-green-50 border border-green-200'">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <template x-if="result.visaQualification === 'VISA_REQUIRED'">
                                                <i class="fa-solid fa-triangle-exclamation text-amber-500 text-2xl"></i>
                                            </template>
                                            <template x-if="result.visaQualification !== 'VISA_REQUIRED'">
                                                <i class="fa-solid fa-circle-check text-green-500 text-2xl"></i>
                                            </template>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold" :class="result.visaQualification === 'VISA_REQUIRED' ? 'text-amber-800' : 'text-green-800'" x-text="result.visaQualification === 'VISA_REQUIRED' ? 'Visum erforderlich' : 'Kein Visum erforderlich'"></h3>
                                            <template x-if="result.visaType">
                                                <p class="text-sm font-medium" :class="result.visaQualification === 'VISA_REQUIRED' ? 'text-amber-700' : 'text-green-700'" x-text="result.visaType"></p>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <template x-if="result.description">
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
                                            <i class="fa-regular fa-file-lines text-blue-500"></i>
                                            Beschreibung
                                        </h4>
                                        <p class="text-sm text-gray-700" x-text="result.description"></p>
                                    </div>
                                </template>

                                <!-- Visa Time -->
                                <template x-if="result.visaTime">
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
                                            <i class="fa-regular fa-clock text-blue-500"></i>
                                            Bearbeitungszeit
                                        </h4>
                                        <p class="text-sm text-gray-700" x-text="result.visaTime"></p>
                                    </div>
                                </template>

                                <!-- Visa Documents -->
                                <template x-if="result.visaDocs">
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
                                            <i class="fa-regular fa-folder-open text-blue-500"></i>
                                            Erforderliche Dokumente
                                        </h4>
                                        <p class="text-sm text-gray-700" x-text="result.visaDocs"></p>
                                    </div>
                                </template>

                                <!-- Visa Fees -->
                                <template x-if="result.visaFees">
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
                                            <i class="fa-regular fa-credit-card text-blue-500"></i>
                                            Gebühren
                                        </h4>
                                        <p class="text-sm text-gray-700" x-text="result.visaFees"></p>
                                    </div>
                                </template>

                                <!-- Raw Response (Debug) -->
                                <details class="border border-gray-200 rounded-lg">
                                    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        API-Antwort anzeigen
                                    </summary>
                                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                                        <pre class="text-xs text-gray-600 overflow-x-auto whitespace-pre-wrap" x-text="JSON.stringify(result, null, 2)"></pre>
                                    </div>
                                </details>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <x-public-footer />
    </div>

    <script>
        function businessVisaForm() {
            return {
                formData: {
                    nationality: '',
                    secondNationality: '',
                    residenceCountry: '',
                    destinationCountry: '',
                    tripStartDate: '',
                    tripEndDate: '',
                    tripReason: '',
                },
                loading: false,
                error: null,
                result: null,
                debugLog: [],
                today: new Date().toISOString().split('T')[0],

                async submitForm() {
                    // Validate
                    if (!this.formData.nationality || !this.formData.residenceCountry ||
                        !this.formData.destinationCountry || !this.formData.tripStartDate ||
                        !this.formData.tripEndDate || !this.formData.tripReason) {
                        this.error = 'Bitte füllen Sie alle Pflichtfelder aus.';
                        return;
                    }

                    this.loading = true;
                    this.error = null;
                    this.result = null;
                    this.debugLog = [];

                    try {
                        const response = await fetch('{{ route("business-visa.check") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.formData),
                        });

                        const data = await response.json();

                        // Always capture debug log if available
                        if (data.debug) {
                            this.debugLog = data.debug;
                        }

                        if (data.success) {
                            this.result = data.data;
                        } else {
                            this.error = data.error || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        this.error = 'Ein Netzwerkfehler ist aufgetreten. Bitte überprüfen Sie Ihre Internetverbindung.';
                    } finally {
                        this.loading = false;
                    }
                },

                formatDetails(details) {
                    if (typeof details === 'string') {
                        return details.replace(/\n/g, '<br>');
                    }
                    return '';
                }
            };
        }
    </script>
</body>
</html>
