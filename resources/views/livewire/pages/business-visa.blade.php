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
            border-top: 3px solid #1e3a5f;
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
            color: #1e3a5f;
            margin-bottom: 0.25rem;
        }

        .field-label .required {
            color: #3b82f6;
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
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .radio-option {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.15s;
        }

        .radio-option:last-child {
            border-bottom: none;
        }

        .radio-option:hover {
            background: #f8fafc;
        }

        .radio-option input[type="radio"] {
            width: 1rem;
            height: 1rem;
            margin-right: 0.75rem;
            accent-color: #1e3a5f;
        }

        .result-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 1.5rem;
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
                <!-- Page Title -->
                <div class="bg-white border-b border-gray-200 px-6 py-4">
                    <h1 class="text-xl font-semibold text-gray-900">Business Visum Check</h1>
                    <p class="text-sm text-gray-500">Prüfen Sie die Visumbestimmungen für Ihre Geschäftsreise</p>
                </div>

                <!-- Two Column Layout -->
                <div class="flex gap-6 p-6">
                    <!-- Left Column: Form -->
                    <div class="w-[55%] min-w-0">
                        <div class="bg-white rounded-lg shadow-sm">
                            <form @submit.prevent="submitForm" class="p-6">
                                <!-- Staatsangehörigkeit -->
                                <div class="mb-6">
                                    <div class="section-label">Staatsangehörigkeit</div>
                                    <div class="grid grid-cols-2 gap-4">
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
                                            <label class="field-label">Zweite Staatsangehörigkeit</label>
                                            <select x-model="formData.secondNationality" class="form-input">
                                                <option value="">Keine</option>
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
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="field-label">Wohnsitzland <span class="required">*</span></label>
                                            <select x-model="formData.residenceCountry" class="form-input" required>
                                                <option value="">Bitte wählen...</option>
                                                @foreach($countries as $code => $name)
                                                    <option value="{{ $code }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
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
                                </div>

                                <!-- Reisezeitraum -->
                                <div class="mb-6">
                                    <div class="section-label">Reisezeitraum</div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="field-label">Reisebeginn <span class="required">*</span></label>
                                            <input
                                                type="date"
                                                x-model="formData.tripStartDate"
                                                :min="today"
                                                class="form-input"
                                                required
                                            >
                                        </div>
                                        <div>
                                            <label class="field-label">Reiseende <span class="required">*</span></label>
                                            <input
                                                type="date"
                                                x-model="formData.tripEndDate"
                                                :min="formData.tripStartDate || today"
                                                class="form-input"
                                                required
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Reisegrund -->
                                <div class="mb-6">
                                    <div class="section-label">Reisegrund</div>
                                    @foreach($groupedReasons as $group => $reasons)
                                        <div class="mb-4">
                                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ $group }}</div>
                                            <div class="border border-gray-100 rounded-lg overflow-hidden">
                                                @foreach($reasons as $code => $reason)
                                                    <label class="radio-option">
                                                        <input
                                                            type="radio"
                                                            name="tripReason"
                                                            value="{{ $code }}"
                                                            x-model="formData.tripReason"
                                                        >
                                                        <span class="text-sm text-gray-700">{{ $reason['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
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
                                    class="w-full py-3 px-4 bg-[#1e3a5f] hover:bg-[#2d4a6f] disabled:bg-[#1e3a5f]/70 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
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
                    </div>

                    <!-- Right Column: Results -->
                    <div class="w-[45%] flex-shrink-0">
                        <div class="result-card">
                            <!-- Empty State -->
                            <div x-show="!result" class="p-8 text-center">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-arrow-right text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">Füllen Sie das Formular aus,<br>um die Visumbestimmungen<br>zu prüfen.</p>
                            </div>

                            <!-- Results -->
                            <div x-show="result" x-cloak class="p-5">
                                <!-- Visa Status -->
                                <div class="mb-4 p-4 rounded-lg" :class="result && result.visaQualification === 'VISA_REQUIRED' ? 'bg-amber-50 border border-amber-200' : 'bg-green-50 border border-green-200'">
                                    <div class="flex items-center gap-3">
                                        <template x-if="result && result.visaQualification === 'VISA_REQUIRED'">
                                            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl"></i>
                                        </template>
                                        <template x-if="result && result.visaQualification !== 'VISA_REQUIRED'">
                                            <i class="fa-solid fa-circle-check text-green-500 text-xl"></i>
                                        </template>
                                        <div>
                                            <div class="font-semibold text-sm" :class="result && result.visaQualification === 'VISA_REQUIRED' ? 'text-amber-800' : 'text-green-800'" x-text="result && result.visaQualification === 'VISA_REQUIRED' ? 'Visum erforderlich' : 'Kein Visum erforderlich'"></div>
                                            <div x-show="result && result.visaType" class="text-xs mt-0.5" :class="result && result.visaQualification === 'VISA_REQUIRED' ? 'text-amber-700' : 'text-green-700'" x-text="result ? result.visaType : ''"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <template x-if="result && result.description">
                                    <div class="mb-4">
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Beschreibung</div>
                                        <p class="text-sm text-gray-700 leading-relaxed" x-text="result.description"></p>
                                    </div>
                                </template>

                                <!-- Processing Time -->
                                <template x-if="result && result.visaTime">
                                    <div class="mb-4">
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Bearbeitungszeit</div>
                                        <p class="text-sm text-gray-700" x-text="result.visaTime"></p>
                                    </div>
                                </template>

                                <!-- Documents -->
                                <template x-if="result && result.visaDocs">
                                    <div class="mb-4">
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Erforderliche Dokumente</div>
                                        <ul class="space-y-1.5">
                                            <template x-for="doc in result.visaDocs.split(';').map(d => d.trim()).filter(d => d)" :key="doc">
                                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                                    <i class="fa-solid fa-check text-green-500 mt-0.5 text-xs"></i>
                                                    <span x-text="doc"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>

                                <!-- Fees -->
                                <template x-if="result && result.visaFees">
                                    <div class="mb-4">
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Gebühren</div>
                                        <ul class="space-y-1.5">
                                            <template x-for="fee in result.visaFees.split(';').map(f => f.trim()).filter(f => f)" :key="fee">
                                                <li class="flex items-start gap-2 text-sm text-gray-700">
                                                    <i class="fa-solid fa-euro-sign text-blue-500 mt-0.5 text-xs"></i>
                                                    <span x-text="fee"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>

                                <!-- Raw Response Toggle -->
                                <details class="mt-4 border-t border-gray-100 pt-4">
                                    <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">API-Antwort anzeigen</summary>
                                    <pre class="mt-2 text-xs text-gray-500 overflow-x-auto whitespace-pre-wrap bg-gray-50 p-2 rounded" x-text="JSON.stringify(result, null, 2)"></pre>
                                </details>
                            </div>
                        </div>
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
                today: new Date().toISOString().split('T')[0],

                async submitForm() {
                    if (!this.formData.nationality || !this.formData.residenceCountry ||
                        !this.formData.destinationCountry || !this.formData.tripStartDate ||
                        !this.formData.tripEndDate || !this.formData.tripReason) {
                        this.error = 'Bitte füllen Sie alle Pflichtfelder aus.';
                        return;
                    }

                    this.loading = true;
                    this.error = null;
                    this.result = null;

                    try {
                        const fetchStart = performance.now();
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
                        window.debugPanel?.log('/business-visa/check', this.formData, data, performance.now() - fetchStart, data?.debug?.duration_ms);

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
                }
            };
        }
    </script>

    <x-debug-panel :isDebugUser="$isDebugUser ?? false" />
</body>
</html>
