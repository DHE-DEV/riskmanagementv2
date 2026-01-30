<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visum Check (VisumPoint) - Global Travel Monitor</title>

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
            var fallbackHref = '{{ file_exists(public_path('vendor/fontawesome/css/all.min.css')) ? asset('vendor/fontawesome/css/all.min.css') : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css' }}';
            window.addEventListener('DOMContentLoaded', function(){
                setTimeout(function(){ if(!window.__faKitOk){ addCss(fallbackHref); } }, 800);
            });
        })();
        </script>
    @elseif (file_exists(public_path('vendor/fontawesome/css/all.min.css')))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
    @else
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" />
    @endif
    <style>
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

        .header {
            flex-shrink: 0;
            height: 64px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            z-index: 10000;
        }

        .footer {
            flex-shrink: 0;
            height: 32px;
            background: white;
            color: black;
            z-index: 9999;
            border-top: 1px solid #e5e7eb;
        }

        .main-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }

        .navigation {
            flex-shrink: 0;
            width: 64px;
            background: black;
        }

        .content-area {
            flex: 1;
            overflow-y: auto;
            background: #f5f5f5;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .loading-spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #059669;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .section-label {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .field-label {
            font-size: 0.875rem;
            color: #065f46;
            margin-bottom: 0.25rem;
        }

        .field-label .required {
            color: #059669;
        }

        .form-input {
            width: 100%;
            padding: 0.625rem 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: #334155;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .result-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 1.5rem;
        }

        .visa-type-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }

        .requirement-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
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
            <x-public-navigation active="visumpoint-check" />

            <!-- Content Area -->
            <div class="content-area" x-data="visumPointForm()">
                <!-- Page Title -->
                <div class="bg-white border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">Visum Check</h1>
                            <p class="text-sm text-gray-500">Prüfen Sie Visumbestimmungen mit VisumPoint</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <span>Powered by</span>
                            <span class="font-semibold text-emerald-600">VisumPoint</span>
                        </div>
                    </div>
                </div>

                @if(!$isConfigured)
                <!-- Not Configured Warning -->
                <div class="p-6">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-amber-800">Service nicht konfiguriert</h3>
                                <p class="text-sm text-amber-700 mt-1">
                                    Der VisumPoint Service ist noch nicht konfiguriert. Bitte hinterlegen Sie die API-Zugangsdaten in der Konfiguration.
                                </p>
                                <div class="mt-3 p-3 bg-amber-100 rounded text-xs font-mono text-amber-800">
                                    VISUMPOINT_ORGANIZATION=IhreOrganisation<br>
                                    VISUMPOINT_ACCESS_TOKEN=IhrAccessToken
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <!-- Two Column Layout -->
                <div class="flex gap-6 p-6">
                    <!-- Left Column: Form -->
                    <div class="w-[45%] min-w-0">
                        <div class="bg-white rounded-lg shadow-sm">
                            <form @submit.prevent="submitForm" class="p-6">
                                <!-- Staatsangehörigkeit -->
                                <div class="mb-6">
                                    <div class="section-label">Reisender</div>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="field-label">Staatsangehörigkeit <span class="required">*</span></label>
                                            <select x-model="formData.nationality" class="form-input" required>
                                                <option value="">Bitte wählen...</option>
                                                @foreach($countries as $code => $name)
                                                    <option value="{{ $code }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label">Wohnsitzland <span class="required">*</span></label>
                                            <select x-model="formData.residenceCountry" class="form-input" required>
                                                <option value="">Bitte wählen...</option>
                                                @foreach($countries as $code => $name)
                                                    <option value="{{ $code }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reiseziel -->
                                <div class="mb-6">
                                    <div class="section-label">Reiseziel</div>
                                    <div>
                                        <label class="field-label">Zielland <span class="required">*</span></label>
                                        <select x-model="formData.destinationCountry" class="form-input" required>
                                            <option value="">Bitte wählen...</option>
                                            @foreach($countries as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Error Message -->
                                <div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="flex items-center gap-2 text-red-700 text-sm">
                                        <i class="fa-solid fa-circle-exclamation"></i>
                                        <span x-text="error"></span>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-600/70 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
                                >
                                    <template x-if="loading">
                                        <div class="loading-spinner"></div>
                                    </template>
                                    <span x-text="loading ? 'Prüfung läuft...' : 'Visumbestimmungen prüfen'"></span>
                                    <i x-show="!loading" class="fa-solid fa-arrow-right"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Disclaimer -->
                        <p class="text-xs text-gray-400 text-center mt-4 px-4">
                            Die bereitgestellten Informationen werden mit größter Sorgfalt recherchiert. Dennoch kann keine Gewähr für die Richtigkeit übernommen werden.
                        </p>

                        <!-- Link to other service -->
                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-info-circle text-blue-500 mt-0.5"></i>
                                <div>
                                    <p class="text-sm text-blue-800">
                                        Für detaillierte Prüfungen mit Reisezeitraum und Reisegrund nutzen Sie unseren
                                        <a href="{{ route('business-visa') }}" class="font-medium underline hover:text-blue-600">Business Visum Check</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Results -->
                    <div class="w-[55%] flex-shrink-0">
                        <div class="result-card">
                            <!-- Empty State -->
                            <div x-show="!result" class="p-8 text-center">
                                <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-passport text-emerald-400 text-2xl"></i>
                                </div>
                                <h3 class="font-medium text-gray-700 mb-2">Visum-Anforderungen prüfen</h3>
                                <p class="text-sm text-gray-500">Wählen Sie Staatsangehörigkeit, Wohnsitzland<br>und Zielland aus, um die Visumbestimmungen zu prüfen.</p>
                            </div>

                            <!-- Results -->
                            <div x-show="result" x-cloak class="p-5">
                                <!-- Visa Status Header -->
                                <div class="mb-5 p-4 rounded-lg" :class="result && result.visaRequired ? 'bg-amber-50 border border-amber-200' : 'bg-green-50 border border-green-200'">
                                    <div class="flex items-center gap-3">
                                        <template x-if="result && result.visaRequired">
                                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                                <i class="fa-solid fa-passport text-amber-600"></i>
                                            </div>
                                        </template>
                                        <template x-if="result && !result.visaRequired">
                                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                                <i class="fa-solid fa-circle-check text-green-600"></i>
                                            </div>
                                        </template>
                                        <div>
                                            <div class="font-semibold" :class="result && result.visaRequired ? 'text-amber-800' : 'text-green-800'" x-text="result ? result.message : ''"></div>
                                            <div class="text-xs mt-0.5" :class="result && result.visaRequired ? 'text-amber-600' : 'text-green-600'">
                                                <span x-text="result && result.visaTypes ? result.visaTypes.length + ' Visum-Typ(en) gefunden' : ''"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Visa Types -->
                                <template x-if="result && result.visaTypes && result.visaTypes.length > 0">
                                    <div class="mb-5">
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Verfügbare Visum-Typen</div>
                                        <template x-for="(type, index) in result.visaTypes" :key="type.id">
                                            <div class="visa-type-card">
                                                <div class="flex items-start gap-3">
                                                    <div class="w-6 h-6 bg-emerald-200 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-emerald-700" x-text="index + 1"></div>
                                                    <div class="flex-1">
                                                        <p class="text-sm text-gray-700 leading-relaxed" x-text="type.details"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Requirements -->
                                <template x-if="result && result.requirements && result.requirements.length > 0">
                                    <div class="mb-5">
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Anforderungen</div>
                                        <template x-for="req in result.requirements" :key="req.id">
                                            <div class="requirement-item">
                                                <i class="fa-solid fa-file-lines text-amber-500 mt-0.5"></i>
                                                <p class="text-sm text-gray-700" x-text="req.details"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- No Visa Required Message -->
                                <template x-if="result && !result.visaRequired">
                                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex items-start gap-3">
                                            <i class="fa-solid fa-info-circle text-green-500 mt-0.5"></i>
                                            <p class="text-sm text-green-700">
                                                Für diese Kombination aus Staatsangehörigkeit, Wohnsitzland und Zielland ist kein Visum erforderlich.
                                                Bitte beachten Sie dennoch die gültigen Einreisebestimmungen des Ziellandes.
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Raw Response Toggle -->
                                <details class="mt-5 border-t border-gray-100 pt-4">
                                    <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">API-Antwort anzeigen</summary>
                                    <pre class="mt-2 text-xs text-gray-500 overflow-x-auto whitespace-pre-wrap bg-gray-50 p-3 rounded max-h-64 overflow-y-auto" x-text="JSON.stringify(result, null, 2)"></pre>
                                </details>

                                <!-- Debug Log Toggle -->
                                <details class="mt-3 border-t border-gray-100 pt-4" x-show="debugLog && debugLog.length > 0">
                                    <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">
                                        <i class="fa-solid fa-bug mr-1"></i>API-Requests anzeigen (<span x-text="debugLog ? debugLog.length : 0"></span> Aufrufe)
                                    </summary>
                                    <div class="mt-2 space-y-3">
                                        <template x-for="(entry, index) in debugLog" :key="index">
                                            <div class="bg-gray-50 rounded p-3 border border-gray-200">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-semibold text-gray-700" x-text="entry.function"></span>
                                                    <span class="text-xs text-gray-400" x-text="entry.timestamp"></span>
                                                </div>

                                                <!-- cURL Command (masked) -->
                                                <div class="mb-3">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <div class="text-xs font-medium text-purple-600">cURL (maskiert):</div>
                                                        <button
                                                            type="button"
                                                            @click="navigator.clipboard.writeText(entry.curlMasked); $el.textContent = 'Kopiert!'; setTimeout(() => $el.textContent = 'Kopieren', 1500)"
                                                            class="text-xs text-purple-500 hover:text-purple-700"
                                                        >Kopieren</button>
                                                    </div>
                                                    <pre class="text-xs text-gray-600 overflow-x-auto whitespace-pre-wrap bg-purple-50 p-2 rounded font-mono" x-text="entry.curlMasked"></pre>
                                                </div>

                                                <!-- cURL Command (full - collapsible) -->
                                                <details class="mb-3">
                                                    <summary class="text-xs text-orange-600 cursor-pointer hover:text-orange-700">
                                                        <i class="fa-solid fa-key mr-1"></i>cURL mit echtem Token anzeigen
                                                    </summary>
                                                    <div class="mt-1">
                                                        <div class="flex items-center justify-end mb-1">
                                                            <button
                                                                type="button"
                                                                @click="navigator.clipboard.writeText(entry.curl); $el.textContent = 'Kopiert!'; setTimeout(() => $el.textContent = 'Kopieren', 1500)"
                                                                class="text-xs text-orange-500 hover:text-orange-700"
                                                            >Kopieren</button>
                                                        </div>
                                                        <pre class="text-xs text-gray-600 overflow-x-auto whitespace-pre-wrap bg-orange-50 p-2 rounded font-mono border border-orange-200" x-text="entry.curl"></pre>
                                                    </div>
                                                </details>

                                                <!-- Request Body -->
                                                <div class="mb-2">
                                                    <div class="text-xs font-medium text-blue-600 mb-1">Request Body:</div>
                                                    <pre class="text-xs text-gray-600 overflow-x-auto whitespace-pre-wrap bg-blue-50 p-2 rounded" x-text="JSON.stringify(entry.request.bodyMasked, null, 2)"></pre>
                                                </div>

                                                <!-- Response -->
                                                <div>
                                                    <div class="text-xs font-medium mb-1" :class="entry.error ? 'text-red-600' : 'text-green-600'" x-text="entry.error ? 'Error: ' + entry.error : 'Response:'"></div>
                                                    <pre class="text-xs text-gray-600 overflow-x-auto whitespace-pre-wrap p-2 rounded" :class="entry.error ? 'bg-red-50' : 'bg-green-50'" x-text="JSON.stringify(entry.response, null, 2)"></pre>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <x-public-footer />
    </div>

    <script>
        function visumPointForm() {
            return {
                formData: {
                    nationality: '',
                    residenceCountry: '',
                    destinationCountry: '',
                },
                loading: false,
                error: null,
                result: null,
                debugLog: [],

                async submitForm() {
                    if (!this.formData.nationality || !this.formData.residenceCountry || !this.formData.destinationCountry) {
                        this.error = 'Bitte füllen Sie alle Pflichtfelder aus.';
                        return;
                    }

                    this.loading = true;
                    this.error = null;
                    this.result = null;
                    this.debugLog = [];

                    try {
                        const response = await fetch('{{ route("visumpoint.check") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.formData),
                        });

                        const data = await response.json();

                        // Always capture debug log
                        if (data.debugLog) {
                            this.debugLog = data.debugLog;
                        }

                        if (data.success) {
                            this.result = data.data;
                        } else {
                            this.error = data.error || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
                            // Show debug log even on error
                            this.result = { _errorResponse: true };
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        this.error = 'Ein Netzwerkfehler ist aufgetreten. Bitte überprüfen Sie Ihre Internetverbindung.';
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
