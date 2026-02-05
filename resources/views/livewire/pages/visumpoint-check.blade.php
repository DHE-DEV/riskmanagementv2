<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visum Check (VisumPoint) - Global Travel Monitor</title>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Marked.js for Markdown rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>

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

        /* Markdown prose styling */
        .prose h5 {
            font-size: 0.875rem;
            font-weight: 700;
            margin-top: 0.75rem;
            margin-bottom: 0.5rem;
            color: #065f46;
        }

        .prose ul {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            list-style-type: disc;
        }

        .prose li {
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .prose strong {
            font-weight: 600;
            color: #047857;
        }

        .prose p {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .prose-emerald h5 {
            color: #047857;
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
                                <!-- Language Selection -->
                                <div class="mb-6">
                                    <div class="section-label">Sprache / Language / Langue</div>
                                    <div>
                                        <label class="field-label">Ausgabesprache <span class="required">*</span></label>
                                        <select x-model="formData.language" class="form-input" required>
                                            <option value="de">🇩🇪 Deutsch</option>
                                            <option value="en">🇬🇧 English</option>
                                            <option value="fr">🇫🇷 Français</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Die Sprache, in der die Visum-Informationen angezeigt werden</p>
                                    </div>
                                </div>

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

                                <!-- Format Selection -->
                                <div class="mb-6">
                                    <div class="section-label">Ausgabeformat</div>
                                    <div>
                                        <label class="field-label">Format <span class="required">*</span></label>
                                        <select x-model="formData.format" class="form-input" required>
                                            <option value="markdown">
                                                <i class="fa-solid fa-file-lines"></i> Markdown (empfohlen)
                                            </option>
                                            <option value="html">
                                                <i class="fa-brands fa-html5"></i> HTML
                                            </option>
                                            <option value="text">
                                                <i class="fa-solid fa-align-left"></i> Klartext
                                            </option>
                                            <option value="json">
                                                <i class="fa-solid fa-code"></i> JSON
                                            </option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1.5">
                                            <template x-if="formData.format === 'markdown'">
                                                <span><i class="fa-solid fa-info-circle"></i> Beste Formatierung mit Überschriften und Listen</span>
                                            </template>
                                            <template x-if="formData.format === 'html'">
                                                <span><i class="fa-solid fa-info-circle"></i> HTML-formatierte Ausgabe</span>
                                            </template>
                                            <template x-if="formData.format === 'text'">
                                                <span><i class="fa-solid fa-info-circle"></i> Einfache Textausgabe ohne Formatierung</span>
                                            </template>
                                            <template x-if="formData.format === 'json'">
                                                <span><i class="fa-solid fa-info-circle"></i> Strukturierte Daten für Entwickler</span>
                                            </template>
                                        </p>
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
                                <!-- Format Indicator -->
                                <div x-show="result && result.format" class="mb-3 flex items-center gap-2 text-xs text-gray-500">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Ausgabeformat: <span class="font-semibold capitalize" x-text="result.format"></span></span>
                                </div>

                                <!-- Visa Status Header -->
                                <div class="mb-5 p-5 rounded-lg shadow-sm" :class="result && result.visaRequired ? 'bg-gradient-to-r from-amber-50 to-amber-100 border border-amber-200' : 'bg-gradient-to-r from-green-50 to-green-100 border border-green-200'">
                                    <div class="flex items-start gap-4">
                                        <template x-if="result && result.visaRequired">
                                            <div class="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center shadow-md flex-shrink-0">
                                                <i class="fa-solid fa-passport text-white text-xl"></i>
                                            </div>
                                        </template>
                                        <template x-if="result && !result.visaRequired">
                                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center shadow-md flex-shrink-0">
                                                <i class="fa-solid fa-circle-check text-white text-xl"></i>
                                            </div>
                                        </template>
                                        <div class="flex-1">
                                            <div class="font-bold text-lg mb-1" :class="result && result.visaRequired ? 'text-amber-900' : 'text-green-900'" x-text="result ? result.message : ''"></div>

                                            <!-- Travel Details -->
                                            <div class="flex items-center gap-2 text-xs mb-2" :class="result && result.visaRequired ? 'text-amber-700' : 'text-green-700'">
                                                <span x-show="result && result.nationality" class="flex items-center gap-1">
                                                    <i class="fa-solid fa-flag"></i>
                                                    <span x-text="result.nationality"></span>
                                                </span>
                                                <span x-show="result && result.nationality && result.destinationCountry">→</span>
                                                <span x-show="result && result.destinationCountry" class="flex items-center gap-1">
                                                    <i class="fa-solid fa-location-dot"></i>
                                                    <span x-text="result.destinationCountry"></span>
                                                </span>
                                            </div>

                                            <!-- Visa Count -->
                                            <template x-if="result && result.visaRequired && result.visaTypes">
                                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold" :class="result.visaRequired ? 'bg-amber-200 text-amber-800' : 'bg-green-200 text-green-800'">
                                                    <i class="fa-solid fa-layer-group"></i>
                                                    <span x-text="result.visaTypes.length + ' Visum-Typ' + (result.visaTypes.length !== 1 ? 'en' : '')"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- API Errors Display -->
                                <template x-if="result && result.errors && result.errors.length > 0">
                                    <div class="mb-5">
                                        <div class="bg-red-50 border border-red-200 rounded-lg overflow-hidden">
                                            <div class="bg-red-100 px-4 py-3 border-b border-red-200">
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                                                    <h4 class="font-semibold text-red-900 text-sm">API-Fehler während der Verarbeitung</h4>
                                                    <span class="ml-auto text-xs bg-red-200 text-red-800 px-2 py-1 rounded-full font-semibold" x-text="result.errors.length + ' Fehler'"></span>
                                                </div>
                                                <p class="text-xs text-red-700 mt-1">Bei einigen API-Aufrufen sind Fehler aufgetreten. Möglicherweise sind nicht alle Informationen vollständig.</p>
                                            </div>
                                            <div class="p-4 space-y-3">
                                                <template x-for="(error, errorIndex) in result.errors" :key="errorIndex">
                                                    <div class="bg-white border border-red-300 rounded-md p-3">
                                                        <div class="flex items-start gap-2 mb-2">
                                                            <div class="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-semibold text-red-700" x-text="errorIndex + 1"></div>
                                                            <div class="flex-1">
                                                                <div class="text-xs font-semibold text-red-800 mb-1" x-text="error.context"></div>
                                                                <div class="text-sm text-red-700 mb-2" x-text="error.error"></div>

                                                                <!-- Detailed Error Information -->
                                                                <template x-if="error.details">
                                                                    <div class="mt-2 p-2 bg-red-50 rounded text-xs space-y-1">
                                                                        <div x-show="error.errorId" class="flex gap-2">
                                                                            <span class="font-semibold text-red-700">Error ID:</span>
                                                                            <span class="text-red-600 font-mono" x-text="error.errorId || 'N/A'"></span>
                                                                        </div>
                                                                        <div x-show="error.details.ErrorLogID" class="flex gap-2">
                                                                            <span class="font-semibold text-red-700">Log ID:</span>
                                                                            <span class="text-red-600 font-mono" x-text="error.details.ErrorLogID"></span>
                                                                        </div>
                                                                        <div x-show="error.details.ErrorClass" class="flex gap-2">
                                                                            <span class="font-semibold text-red-700">Error Class:</span>
                                                                            <span class="text-red-600 font-mono" x-text="error.details.ErrorClass"></span>
                                                                        </div>
                                                                        <div x-show="error.details.ErrorFile" class="flex gap-2">
                                                                            <span class="font-semibold text-red-700">File:</span>
                                                                            <span class="text-red-600 font-mono text-xs break-all" x-text="error.details.ErrorFile"></span>
                                                                        </div>
                                                                        <div x-show="error.details.ErrorLine" class="flex gap-2">
                                                                            <span class="font-semibold text-red-700">Line:</span>
                                                                            <span class="text-red-600 font-mono" x-text="error.details.ErrorLine"></span>
                                                                        </div>
                                                                        <div x-show="error.details.CatchAction" class="flex gap-2">
                                                                            <span class="font-semibold text-red-700">Context:</span>
                                                                            <span class="text-red-600" x-text="error.details.CatchAction"></span>
                                                                        </div>
                                                                        <div x-show="error.visaTypeId" class="flex gap-2">
                                                                            <span class="font-semibold text-red-700">Visa Type ID:</span>
                                                                            <span class="text-red-600 font-mono text-xs break-all" x-text="error.visaTypeId"></span>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Visa Types with Requirements -->
                                <template x-if="result && result.visaTypes && result.visaTypes.length > 0">
                                    <div class="mb-5">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                                Verfügbare Visum-Typen (<span x-text="result.totalVisaTypes || result.visaTypes.length"></span>)
                                            </div>
                                        </div>

                                        <template x-for="(type, index) in result.visaTypes" :key="type.id">
                                            <div class="mb-4 bg-white border border-emerald-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                                <!-- Visa Type Header -->
                                                <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 p-4 border-b border-emerald-200">
                                                    <div class="flex items-start gap-3">
                                                        <div class="w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-white shadow-sm" x-text="index + 1"></div>
                                                        <div class="flex-1">
                                                            <h4 class="font-semibold text-emerald-900 text-sm mb-2">
                                                                <i class="fa-solid fa-passport text-emerald-600 mr-2"></i>
                                                                Visum-Typ <span x-text="index + 1"></span>
                                                            </h4>
                                                            <div class="prose prose-sm prose-emerald max-w-none text-emerald-800" x-html="formatContent(type.details || '', result.format)"></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Requirements for this Visa Type -->
                                                <template x-if="type.requirements && type.requirements.length > 0">
                                                    <div class="p-4 bg-amber-50/50">
                                                        <div class="flex items-center gap-2 mb-3">
                                                            <i class="fa-solid fa-list-check text-amber-600"></i>
                                                            <div class="text-xs font-semibold text-amber-800 uppercase tracking-wide">
                                                                Anforderungen (<span x-text="type.requirements.length"></span>)
                                                            </div>
                                                        </div>
                                                        <div class="space-y-2">
                                                            <template x-for="(req, reqIndex) in type.requirements" :key="req.id">
                                                                <div class="flex items-start gap-3 p-4 bg-white border border-amber-200 rounded-md">
                                                                    <div class="w-5 h-5 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-semibold text-amber-700 mt-1" x-text="reqIndex + 1"></div>
                                                                    <div class="prose prose-sm max-w-none text-gray-700 flex-1" x-html="formatContent(req.details || '', result.format)"></div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <!-- No Requirements -->
                                                <template x-if="!type.requirements || type.requirements.length === 0">
                                                    <div class="p-4 bg-gray-50">
                                                        <p class="text-xs text-gray-500 italic flex items-center gap-2">
                                                            <i class="fa-solid fa-info-circle"></i>
                                                            Keine spezifischen Anforderungen verfügbar
                                                        </p>
                                                    </div>
                                                </template>
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
                    language: 'de',
                    nationality: '',
                    residenceCountry: '',
                    destinationCountry: '',
                    format: 'markdown',
                },
                loading: false,
                error: null,
                result: null,
                debugLog: [],

                formatContent(content, format) {
                    if (!content) return '';

                    switch (format) {
                        case 'markdown':
                            return marked.parse(content);
                        case 'html':
                            return content;
                        case 'text':
                            return '<pre class="whitespace-pre-wrap font-sans">' + content + '</pre>';
                        case 'json':
                            try {
                                const parsed = JSON.parse(content);
                                return '<pre class="text-xs overflow-x-auto">' + JSON.stringify(parsed, null, 2) + '</pre>';
                            } catch (e) {
                                return '<pre class="whitespace-pre-wrap">' + content + '</pre>';
                            }
                        default:
                            return marked.parse(content);
                    }
                },

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
