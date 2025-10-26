@extends('layouts.public')

@section('title', 'Customer Dashboard - Global Travel Monitor')

@php
    $active = 'dashboard';
@endphp

@section('content')
    <!-- Sidebar -->
    <div class="sidebar" x-data="branchManager()">
        <div class="p-4">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fa-regular fa-building mr-2"></i>
                Filialen & Standorte
            </h2>

            <div class="bg-white p-4 rounded-lg border border-gray-200 mb-4">
                <p class="text-sm text-gray-600">
                    Hier können Sie Ihre Filialen und Standorte verwalten.
                </p>
            </div>

            <div class="space-y-3">
                <button @click="openModal" class="w-full px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                    <i class="fa-regular fa-plus"></i>
                    Neue Filiale hinzufügen
                </button>

                <div class="grid grid-cols-2 gap-2">
                    <button @click="importBranches" class="px-3 py-1 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2 text-sm">
                        <i class="fa-regular fa-file-import"></i>
                        Importieren
                    </button>
                    <button @click="exportBranches" class="px-3 py-1 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2 text-sm">
                        <i class="fa-regular fa-file-export"></i>
                        Exportieren
                    </button>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    Ihre Filialen <span id="branches-count" class="text-gray-500">(0)</span>
                </h3>
                <div class="space-y-2" id="branches-list">
                    <!-- Branches werden hier dynamisch geladen -->
                </div>
            </div>
        </div>

        <!-- Modal für neue Filiale -->
        <div x-show="showModal" x-cloak class="modal-overlay fixed inset-0 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop fixed inset-0 bg-black opacity-50" @click="closeModal"></div>
                <div class="modal-content relative bg-white rounded-lg max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold mb-4">Neue Filiale hinzufügen</h3>
                    <form @submit.prevent="saveBranch">
                        <div class="space-y-3">
                            <input type="text" x-model="form.name" placeholder="Filialname" required class="w-full px-3 py-2 border rounded">
                            <input type="text" x-model="form.street" placeholder="Straße" required class="w-full px-3 py-2 border rounded">
                            <input type="text" x-model="form.house_number" placeholder="Hausnummer" class="w-full px-3 py-2 border rounded">
                            <input type="text" x-model="form.postal_code" placeholder="PLZ" required class="w-full px-3 py-2 border rounded">
                            <input type="text" x-model="form.city" placeholder="Stadt" required class="w-full px-3 py-2 border rounded">
                            <input type="text" x-model="form.country" placeholder="Land" required class="w-full px-3 py-2 border rounded">
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Speichern</button>
                            <button type="button" @click="closeModal" class="flex-1 bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Abbrechen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal für Import -->
        <div x-show="showImportModal" x-cloak class="modal-overlay fixed inset-0 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="modal-backdrop fixed inset-0 bg-black opacity-50" @click="closeImportModal"></div>
                <div class="modal-content relative bg-white rounded-lg max-w-lg w-full p-6">
                    <h3 class="text-lg font-semibold mb-4">Filialen importieren</h3>

                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <i class="fa-regular fa-info-circle mr-1"></i>
                            Nur CSV-Dateien werden unterstützt. Format: Name, Zusatz, Straße, Hausnummer, PLZ, Stadt, Land
                        </p>
                    </div>

                    <!-- Drag & Drop Area -->
                    <div
                        @drop.prevent="handleFileDrop($event)"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50'"
                        class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
                        @click="$refs.fileInput.click()">
                        <i class="fa-regular fa-cloud-upload text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-700 font-medium mb-1">Datei hier ablegen</p>
                        <p class="text-sm text-gray-500">oder klicken zum Durchsuchen</p>
                        <input
                            type="file"
                            x-ref="fileInput"
                            @change="handleFileSelect($event)"
                            accept=".csv"
                            class="hidden">
                    </div>

                    <div x-show="selectedFileName" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800">
                            <i class="fa-regular fa-file-csv mr-1"></i>
                            <span x-text="selectedFileName"></span>
                        </p>
                    </div>

                    <div class="mt-6 flex gap-2">
                        <button
                            @click="processImport"
                            :disabled="!selectedFile"
                            :class="selectedFile ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed'"
                            class="flex-1 text-white px-4 py-2 rounded transition-colors">
                            Importieren
                        </button>
                        <button type="button" @click="closeImportModal" class="flex-1 bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Abbrechen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Karte für Filialen -->
            @if(auth('customer')->user()->branch_management_active)
            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200 mb-6" x-data="{ recenterMap() { window.dispatchEvent(new CustomEvent('recenter-map')); } }">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">
                        <i class="fa-regular fa-map-location-dot mr-2"></i>
                        Standorte auf der Karte
                    </h2>
                    <button @click="recenterMap()" class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4" fill="currentColor">
                            <path d="M320 544C443.7 544 544 443.7 544 320C544 196.3 443.7 96 320 96C196.3 96 96 196.3 96 320C96 325.9 96.2 331.8 96.7 337.6L91.8 339.2C81.9 342.6 73.3 348.1 66.4 355.1C64.8 343.6 64 331.9 64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C308.1 576 296.4 575.2 284.9 573.6C291.9 566.7 297.4 558 300.7 548.2L302.3 543.3C308.1 543.8 314 544 319.9 544zM320 160C408.4 160 480 231.6 480 320C480 407.2 410.2 478.1 323.5 480L334.4 447.2C398.3 440 448 385.8 448 320C448 249.3 390.7 192 320 192C254.2 192 200 241.7 192.8 305.6L160 316.5C161.9 229.8 232.8 160 320 160zM315.3 324.7C319.6 329 321.1 335.3 319.2 341.1L255.2 533.1C253 539.6 246.9 544 240 544C233.1 544 227 539.6 224.8 533.1L201 461.6L107.3 555.3C101.1 561.5 90.9 561.5 84.7 555.3C78.5 549.1 78.5 538.9 84.7 532.7L178.4 439L107 415.2C100.4 413 96 406.9 96 400C96 393.1 100.4 387 106.9 384.8L298.9 320.8C304.6 318.9 311 320.4 315.3 324.7zM162.6 400L213.1 416.8C217.9 418.4 221.6 422.1 223.2 426.9L240 477.4L278.7 361.3L162.6 400z"></path>
                        </svg>
                        Karte zentrieren
                    </button>
                </div>
                <div id="branches-map"></div>
            </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <div class="mb-6">
                    <p class="text-gray-600 mt-1">
                        Willkommen, {{ auth('customer')->user()->name }}!
                    </p>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(!auth('customer')->user()->hide_profile_completion)
                <div class="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200"
                     x-data="{
                         customerType: '{{ auth('customer')->user()->customer_type ?? '' }}',
                         businessTypes: {{ json_encode(auth('customer')->user()->business_type ?? []) }},
                         saving: false,
                         async updateCustomerType(type) {
                             console.log('Updating customer type to:', type);
                             this.customerType = type;
                             this.saving = true;

                             // Wenn Privatkunde gewählt wird, Geschäftstypen leeren
                             if (type === 'private' && this.businessTypes.length > 0) {
                                 this.businessTypes = [];
                                 await this.saveBusinessTypes();
                             }

                             try {
                                 const response = await fetch('{{ route('customer.profile.update-customer-type') }}', {
                                     method: 'POST',
                                     headers: {
                                         'Content-Type': 'application/json',
                                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                         'Accept': 'application/json'
                                     },
                                     body: JSON.stringify({ customer_type: type })
                                 });

                                 console.log('Response status:', response.status);
                                 const data = await response.json();
                                 console.log('Response data:', data);

                                 if (data.success) {
                                     console.log('Dispatching event with label:', data.customer_type_label);
                                     window.dispatchEvent(new CustomEvent('customer-type-updated', {
                                         detail: {
                                             type: data.customer_type,
                                             label: data.customer_type_label
                                         }
                                     }));
                                 } else {
                                     console.error('Save failed:', data);
                                 }
                             } catch (error) {
                                 console.error('Fehler beim Speichern:', error);
                                 alert('Fehler beim Speichern des Kundentyps. Bitte versuchen Sie es erneut.');
                             } finally {
                                 this.saving = false;
                             }
                         },
                         toggleBusinessType(type) {
                             const index = this.businessTypes.indexOf(type);
                             if (index > -1) {
                                 this.businessTypes.splice(index, 1);
                             } else {
                                 this.businessTypes.push(type);
                             }
                             this.saveBusinessTypes();
                         },
                         async saveBusinessTypes() {
                             console.log('Saving business types:', this.businessTypes);
                             try {
                                 const response = await fetch('{{ route('customer.profile.update-business-type') }}', {
                                     method: 'POST',
                                     headers: {
                                         'Content-Type': 'application/json',
                                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                         'Accept': 'application/json'
                                     },
                                     body: JSON.stringify({ business_types: this.businessTypes })
                                 });
                                 const data = await response.json();
                                 console.log('Business types response:', data);
                                 if (data.success) {
                                     window.dispatchEvent(new CustomEvent('business-type-updated', {
                                         detail: {
                                             types: data.business_types,
                                             labels: data.business_type_labels
                                         }
                                     }));
                                 }
                             } catch (error) {
                                 console.error('Fehler beim Speichern:', error);
                             }
                         },
                         isBusinessTypeSelected(type) {
                             return this.businessTypes.includes(type);
                         },
                         async toggleHideProfileCompletion(event) {
                             if (event.target.checked) {
                                 try {
                                     const response = await fetch('{{ route('customer.profile.hide-profile-completion') }}', {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                             'Accept': 'application/json'
                                         },
                                         body: JSON.stringify({ hide: true })
                                     });
                                     const data = await response.json();
                                     console.log('Hide profile completion response:', data);
                                     if (data.success) {
                                         // Seite neu laden um den Bereich auszublenden
                                         window.location.reload();
                                     }
                                 } catch (error) {
                                     console.error('Fehler beim Speichern:', error);
                                     event.target.checked = false;
                                     alert('Fehler beim Speichern. Bitte versuchen Sie es erneut.');
                                 }
                             }
                         }
                     }">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Profil vervollständigen
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Es fehlen nur noch wenige Schritte, bis Ihr Profil vollständig erfasst ist. Um den vollen Funktionsumfang nutzen zu können, führen Sie bitte die ausstehenden Aktionen aus.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="block p-4 bg-white rounded-lg border border-gray-200">
                            <h3 class="font-semibold text-gray-900 mb-2">Kundentype</h3>
                            <p class="text-sm text-gray-600 mb-4">Bitte wählen Sie aus, ob Sie Firmenkunde oder Privatkunde sind.</p>
                            <div class="flex gap-3">
                                <button
                                    @click="updateCustomerType('business')"
                                    :class="customerType === 'business' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-white text-gray-700 border border-gray-300'"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:shadow-md"
                                >
                                    Firmenkunde
                                </button>
                                <button
                                    @click="updateCustomerType('private')"
                                    :class="customerType === 'private' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-white text-gray-700 border border-gray-300'"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:shadow-md"
                                >
                                    Privatkunde
                                </button>
                            </div>
                        </div>
                        <div class="block p-4 bg-white rounded-lg border border-gray-200"
                             x-show="customerType === 'business'"
                             x-transition>
                            <h3 class="font-semibold text-gray-900 mb-2">Geschäftstype</h3>
                            <p class="text-sm text-gray-600 mb-4">Bitte wählen Sie den Tätigkeitsbereich aus (Mehrfachauswahl möglich).</p>
                            <div class="flex gap-3 flex-wrap">
                                <button
                                    @click="toggleBusinessType('travel_agency')"
                                    :class="isBusinessTypeSelected('travel_agency') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-white text-gray-700 border border-gray-300'"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:shadow-md"
                                >
                                    Reisebüro
                                </button>
                                <button
                                    @click="toggleBusinessType('organizer')"
                                    :class="isBusinessTypeSelected('organizer') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-white text-gray-700 border border-gray-300'"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:shadow-md"
                                >
                                    Veranstalter
                                </button>
                                <button
                                    @click="toggleBusinessType('online_provider')"
                                    :class="isBusinessTypeSelected('online_provider') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-white text-gray-700 border border-gray-300'"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:shadow-md"
                                >
                                    Online Anbieter
                                </button>
                                <button
                                    @click="toggleBusinessType('mobile_travel_consultant')"
                                    :class="isBusinessTypeSelected('mobile_travel_consultant') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-white text-gray-700 border border-gray-300'"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors hover:shadow-md"
                                >
                                    Mobiler Reiseberater
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6" id="RegisterCompanyAddressSection"
                            x-show="customerType === 'business'"
                            x-transition>
                        <div class="block p-4 bg-white rounded-lg border border-gray-200" id="RegisterCompanyAddress"
                             x-data="{
                                 isEditing: false,
                                 companyName: '{{ auth('customer')->user()->company_name ?? '' }}',
                                 companyAdditional: '{{ auth('customer')->user()->company_additional ?? '' }}',
                                 companyStreet: '{{ auth('customer')->user()->company_street ?? '' }}',
                                 companyHouseNumber: '{{ auth('customer')->user()->company_house_number ?? '' }}',
                                 companyPostalCode: '{{ auth('customer')->user()->company_postal_code ?? '' }}',
                                 companyCity: '{{ auth('customer')->user()->company_city ?? '' }}',
                                 companyCountry: '{{ auth('customer')->user()->company_country ?? '' }}',
                                 countries: [],
                                 filteredCountries: [],
                                 showCountryDropdown: false,
                                 selectedCountryIndex: -1,
                                 async loadCountries() {
                                     if (this.countries.length === 0) {
                                         const response = await fetch('{{ route('customer.profile.get-countries') }}');
                                         this.countries = await response.json();
                                     }
                                 },
                                 filterCountries() {
                                     if (this.companyCountry.length > 0) {
                                         this.filteredCountries = this.countries.filter(c =>
                                             c.name.toLowerCase().includes(this.companyCountry.toLowerCase())
                                         ).slice(0, 10);
                                         this.showCountryDropdown = this.filteredCountries.length > 0;
                                         this.selectedCountryIndex = -1;
                                     } else {
                                         this.filteredCountries = [];
                                         this.showCountryDropdown = false;
                                         this.selectedCountryIndex = -1;
                                     }
                                 },
                                 selectCountry(countryName) {
                                     this.companyCountry = countryName;
                                     this.showCountryDropdown = false;
                                     this.selectedCountryIndex = -1;
                                     this.saveCompanyAddress();
                                 },
                                 handleCountryKeydown(event) {
                                     if (!this.showCountryDropdown || this.filteredCountries.length === 0) return;

                                     if (event.key === 'ArrowDown') {
                                         event.preventDefault();
                                         this.selectedCountryIndex = (this.selectedCountryIndex + 1) % this.filteredCountries.length;
                                     } else if (event.key === 'ArrowUp') {
                                         event.preventDefault();
                                         this.selectedCountryIndex = this.selectedCountryIndex <= 0
                                             ? this.filteredCountries.length - 1
                                             : this.selectedCountryIndex - 1;
                                     } else if (event.key === 'Enter') {
                                         event.preventDefault();
                                         if (this.selectedCountryIndex >= 0) {
                                             this.selectCountry(this.filteredCountries[this.selectedCountryIndex].name);
                                         }
                                     } else if (event.key === 'Escape') {
                                         this.showCountryDropdown = false;
                                         this.selectedCountryIndex = -1;
                                     }
                                 },
                                 async saveCompanyAddress() {
                                     try {
                                         const response = await fetch('{{ route('customer.profile.update-company-address') }}', {
                                             method: 'POST',
                                             headers: {
                                                 'Content-Type': 'application/json',
                                                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                 'Accept': 'application/json'
                                             },
                                             body: JSON.stringify({
                                                 company_name: this.companyName,
                                                 company_additional: this.companyAdditional,
                                                 company_street: this.companyStreet,
                                                 company_house_number: this.companyHouseNumber,
                                                 company_postal_code: this.companyPostalCode,
                                                 company_city: this.companyCity,
                                                 company_country: this.companyCountry
                                             })
                                         });
                                         const data = await response.json();
                                         if (data.success) {
                                             console.log('Firmenanschrift gespeichert');
                                             window.dispatchEvent(new CustomEvent('company-address-updated', {
                                                 detail: {
                                                     companyName: this.companyName,
                                                     companyAdditional: this.companyAdditional,
                                                     companyStreet: this.companyStreet,
                                                     companyHouseNumber: this.companyHouseNumber,
                                                     companyPostalCode: this.companyPostalCode,
                                                     companyCity: this.companyCity,
                                                     companyCountry: this.companyCountry
                                                 }
                                             }));
                                         }
                                     } catch (error) {
                                         console.error('Fehler beim Speichern:', error);
                                     }
                                 }
                             }">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">Firmenanschrift</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-orange-50 text-orange-700 text-xs font-medium rounded border border-orange-200 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                        </svg>
                                        Angabe empfohlen
                                    </span>
                                    <button @click="isEditing = !isEditing" class="p-1 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-400 mb-4">Bitte geben Sie die Anschrift Ihres Unternehmens ein. Diese Anschrift wird zur Veröffentlichung verwendet, wenn Sie dem Zustimmen.</p>
                            <div class="space-y-3">
                                <input type="text" x-model="companyName" :disabled="!isEditing" @blur="isEditing && saveCompanyAddress()" @keydown.enter="isEditing && saveCompanyAddress()"
                                       placeholder="Firmenname"
                                       :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="companyAdditional" :disabled="!isEditing" @blur="isEditing && saveCompanyAddress()" @keydown.enter="isEditing && saveCompanyAddress()"
                                       placeholder="Zusatz"
                                       :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="grid grid-cols-3 gap-3">
                                    <input type="text" x-model="companyStreet" :disabled="!isEditing" @blur="isEditing && saveCompanyAddress()" @keydown.enter="isEditing && saveCompanyAddress()"
                                           placeholder="Straße"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="companyHouseNumber" :disabled="!isEditing" @blur="isEditing && saveCompanyAddress()" @keydown.enter="isEditing && saveCompanyAddress()"
                                           placeholder="Nr."
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" x-model="companyPostalCode" :disabled="!isEditing" @blur="isEditing && saveCompanyAddress()" @keydown.enter="isEditing && saveCompanyAddress()"
                                           placeholder="PLZ"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="companyCity" :disabled="!isEditing" @blur="isEditing && saveCompanyAddress()" @keydown.enter="isEditing && saveCompanyAddress()"
                                           placeholder="Stadt"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="relative">
                                    <input type="text"
                                           x-model="companyCountry"
                                           :disabled="!isEditing"
                                           @input="isEditing && filterCountries()"
                                           @keydown="isEditing && handleCountryKeydown($event)"
                                           @focus="isEditing && (loadCountries(), filterCountries())"
                                           @blur="setTimeout(() => showCountryDropdown = false, 200)"
                                           placeholder="Land"
                                           autocomplete="off"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div x-show="showCountryDropdown"
                                         class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        <template x-for="(country, index) in filteredCountries" :key="country.id">
                                            <div @click="selectCountry(country.name)"
                                                 :class="index === selectedCountryIndex ? 'bg-blue-100' : 'hover:bg-blue-50'"
                                                 class="px-3 py-2 cursor-pointer text-sm"
                                                 x-text="country.name"></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="block p-4 bg-white rounded-lg border border-gray-200" id="RegisterCompanyInvoiceAddress"
                             x-data="{
                                 isEditing: false,
                                 billingCompanyName: '{{ auth('customer')->user()->billing_company_name ?? '' }}',
                                 billingAdditional: '{{ auth('customer')->user()->billing_additional ?? '' }}',
                                 billingStreet: '{{ auth('customer')->user()->billing_street ?? '' }}',
                                 billingHouseNumber: '{{ auth('customer')->user()->billing_house_number ?? '' }}',
                                 billingPostalCode: '{{ auth('customer')->user()->billing_postal_code ?? '' }}',
                                 billingCity: '{{ auth('customer')->user()->billing_city ?? '' }}',
                                 billingCountry: '{{ auth('customer')->user()->billing_country ?? '' }}',
                                 countries: [],
                                 filteredBillingCountries: [],
                                 showBillingCountryDropdown: false,
                                 selectedBillingCountryIndex: -1,
                                 async loadCountries() {
                                     if (this.countries.length === 0) {
                                         const response = await fetch('{{ route('customer.profile.get-countries') }}');
                                         this.countries = await response.json();
                                     }
                                 },
                                 filterBillingCountries() {
                                     if (this.billingCountry.length > 0) {
                                         this.filteredBillingCountries = this.countries.filter(c =>
                                             c.name.toLowerCase().includes(this.billingCountry.toLowerCase())
                                         ).slice(0, 10);
                                         this.showBillingCountryDropdown = this.filteredBillingCountries.length > 0;
                                         this.selectedBillingCountryIndex = -1;
                                     } else {
                                         this.filteredBillingCountries = [];
                                         this.showBillingCountryDropdown = false;
                                         this.selectedBillingCountryIndex = -1;
                                     }
                                 },
                                 selectBillingCountry(countryName) {
                                     this.billingCountry = countryName;
                                     this.showBillingCountryDropdown = false;
                                     this.selectedBillingCountryIndex = -1;
                                     this.saveBillingAddress();
                                 },
                                 handleBillingCountryKeydown(event) {
                                     if (!this.showBillingCountryDropdown || this.filteredBillingCountries.length === 0) return;

                                     if (event.key === 'ArrowDown') {
                                         event.preventDefault();
                                         this.selectedBillingCountryIndex = (this.selectedBillingCountryIndex + 1) % this.filteredBillingCountries.length;
                                     } else if (event.key === 'ArrowUp') {
                                         event.preventDefault();
                                         this.selectedBillingCountryIndex = this.selectedBillingCountryIndex <= 0
                                             ? this.filteredBillingCountries.length - 1
                                             : this.selectedBillingCountryIndex - 1;
                                     } else if (event.key === 'Enter') {
                                         event.preventDefault();
                                         if (this.selectedBillingCountryIndex >= 0) {
                                             this.selectBillingCountry(this.filteredBillingCountries[this.selectedBillingCountryIndex].name);
                                         }
                                     } else if (event.key === 'Escape') {
                                         this.showBillingCountryDropdown = false;
                                         this.selectedBillingCountryIndex = -1;
                                     }
                                 },
                                 async saveBillingAddress() {
                                     try {
                                         const response = await fetch('{{ route('customer.profile.update-billing-address') }}', {
                                             method: 'POST',
                                             headers: {
                                                 'Content-Type': 'application/json',
                                                 'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                 'Accept': 'application/json'
                                             },
                                             body: JSON.stringify({
                                                 billing_company_name: this.billingCompanyName,
                                                 billing_additional: this.billingAdditional,
                                                 billing_street: this.billingStreet,
                                                 billing_house_number: this.billingHouseNumber,
                                                 billing_postal_code: this.billingPostalCode,
                                                 billing_city: this.billingCity,
                                                 billing_country: this.billingCountry
                                             })
                                         });
                                         const data = await response.json();
                                         if (data.success) {
                                             console.log('Rechnungsadresse gespeichert');
                                             window.dispatchEvent(new CustomEvent('billing-address-updated', {
                                                 detail: {
                                                     billingCompanyName: this.billingCompanyName,
                                                     billingAdditional: this.billingAdditional,
                                                     billingStreet: this.billingStreet,
                                                     billingHouseNumber: this.billingHouseNumber,
                                                     billingPostalCode: this.billingPostalCode,
                                                     billingCity: this.billingCity,
                                                     billingCountry: this.billingCountry
                                                 }
                                             }));
                                         }
                                     } catch (error) {
                                         console.error('Fehler beim Speichern:', error);
                                     }
                                 }
                             }">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">Rechnungsadresse</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 bg-green-50 text-green-700 text-xs font-medium rounded border border-green-200 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Freiwillige Angabe
                                    </span>
                                    <button @click="isEditing = !isEditing" class="p-1 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-400 mb-4">Bitte geben Sie Ihre Rechnungsadresse ein. Diese Anschrift wird nur verwendet, wenn Sie kostenpflichtige Abos abschließen.</p>
                            <div class="space-y-3">
                                <input type="text" x-model="billingCompanyName" :disabled="!isEditing" @blur="isEditing && saveBillingAddress()" @keydown.enter="isEditing && saveBillingAddress()"
                                       placeholder="Firmenname"
                                       :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="billingAdditional" :disabled="!isEditing" @blur="isEditing && saveBillingAddress()" @keydown.enter="isEditing && saveBillingAddress()"
                                       placeholder="Zusatz"
                                       :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="grid grid-cols-3 gap-3">
                                    <input type="text" x-model="billingStreet" :disabled="!isEditing" @blur="isEditing && saveBillingAddress()" @keydown.enter="isEditing && saveBillingAddress()"
                                           placeholder="Straße"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="billingHouseNumber" :disabled="!isEditing" @blur="isEditing && saveBillingAddress()" @keydown.enter="isEditing && saveBillingAddress()"
                                           placeholder="Nr."
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" x-model="billingPostalCode" :disabled="!isEditing" @blur="isEditing && saveBillingAddress()" @keydown.enter="isEditing && saveBillingAddress()"
                                           placeholder="PLZ"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="billingCity" :disabled="!isEditing" @blur="isEditing && saveBillingAddress()" @keydown.enter="isEditing && saveBillingAddress()"
                                           placeholder="Stadt"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="relative">
                                    <input type="text"
                                           x-model="billingCountry"
                                           :disabled="!isEditing"
                                           @input="isEditing && filterBillingCountries()"
                                           @keydown="isEditing && handleBillingCountryKeydown($event)"
                                           @focus="isEditing && (loadCountries(), filterBillingCountries())"
                                           @blur="setTimeout(() => showBillingCountryDropdown = false, 200)"
                                           placeholder="Land"
                                           autocomplete="off"
                                           :class="!isEditing ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div x-show="showBillingCountryDropdown"
                                         class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        <template x-for="(country, index) in filteredBillingCountries" :key="country.id">
                                            <div @click="selectBillingCountry(country.name)"
                                                 :class="index === selectedBillingCountryIndex ? 'bg-blue-100' : 'hover:bg-blue-50'"
                                                 class="px-3 py-2 cursor-pointer text-sm"
                                                 x-text="country.name"></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Checkbox zum Ausblenden des Profil vervollständigen Bereichs -->
                    <div class="mt-6 pt-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   @change="toggleHideProfileCompletion($event)"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Diesen Bereich nicht mehr anzeigen</span>
                        </label>
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-white p-6 rounded-lg border border-gray-200"
                         x-data="{
                             customerTypeLabel: '{{ auth('customer')->user()->customer_type ? (auth('customer')->user()->customer_type === 'business' ? 'Firmenkunde' : 'Privatkunde') : 'Nicht festgelegt' }}',
                             businessTypeLabels: {{ json_encode(array_map(function($type) {
                                 $labels = ['travel_agency' => 'Reisebüro', 'organizer' => 'Veranstalter', 'online_provider' => 'Online Anbieter', 'mobile_travel_consultant' => 'Mobiler Reiseberater'];
                                 return $labels[$type] ?? $type;
                             }, auth('customer')->user()->business_type ?? [])) }}
                         }"
                         @customer-type-updated.window="
                             console.log('Event received in profile:', $event.detail);
                             customerTypeLabel = $event.detail.label;
                         "
                         @business-type-updated.window="
                             console.log('Business type event received in profile:', $event.detail);
                             businessTypeLabels = $event.detail.labels;
                         ">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Profil
                        </h3>
                        <p class="text-sm text-gray-700">
                            {{ auth('customer')->user()->email }}
                        </p>
                        @if(auth('customer')->user()->isSocialLogin())
                            <p class="text-xs text-gray-700 mt-2">
                                Angemeldet mit: {{ ucfirst(auth('customer')->user()->provider) }}
                            </p>
                        @endif
                        <div class="flex flex-wrap gap-2 mt-4">
                            <span
                                x-show="customerTypeLabel && customerTypeLabel !== 'Nicht festgelegt'"
                                x-text="customerTypeLabel"
                                class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded border border-blue-200">
                            </span>
                            <template x-for="label in businessTypeLabels" :key="label">
                                <span
                                    x-text="label"
                                    class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded border border-gray-300">
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            E-Mail Status
                        </h3>
                        <p class="text-sm text-gray-700">
                            @if(auth('customer')->user()->hasVerifiedEmail())
                                <span class="inline-flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Verifiziert
                                </span>
                            @else
                                <span class="text-yellow-600">Nicht verifiziert</span>
                            @endif
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Mitglied seit
                        </h3>
                        <p class="text-sm text-gray-700">
                            {{ auth('customer')->user()->created_at->format('d.m.Y') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6"
                     x-data="{ showBusinessBoxes: '{{ auth('customer')->user()->customer_type ?? '' }}' === 'business' }"
                     x-show="showBusinessBoxes"
                     x-transition
                     @customer-type-updated.window="showBusinessBoxes = ($event.detail.type === 'business')">
                    <div class="bg-white p-6 rounded-lg border border-gray-200"
                         x-data="{
                             isEditing: false,
                             companyName: '{{ auth('customer')->user()->company_name ?? '' }}',
                             companyAdditional: '{{ auth('customer')->user()->company_additional ?? '' }}',
                             companyStreet: '{{ auth('customer')->user()->company_street ?? '' }}',
                             companyHouseNumber: '{{ auth('customer')->user()->company_house_number ?? '' }}',
                             companyPostalCode: '{{ auth('customer')->user()->company_postal_code ?? '' }}',
                             companyCity: '{{ auth('customer')->user()->company_city ?? '' }}',
                             companyCountry: '{{ auth('customer')->user()->company_country ?? '' }}',
                             async saveCompanyAddress() {
                                 try {
                                     const response = await fetch('{{ route('customer.profile.update-company-address') }}', {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                             'Accept': 'application/json'
                                         },
                                         body: JSON.stringify({
                                             company_name: this.companyName,
                                             company_additional: this.companyAdditional,
                                             company_street: this.companyStreet,
                                             company_house_number: this.companyHouseNumber,
                                             company_postal_code: this.companyPostalCode,
                                             company_city: this.companyCity,
                                             company_country: this.companyCountry
                                         })
                                     });
                                     const data = await response.json();
                                     if (data.success) {
                                         this.isEditing = false;
                                         window.dispatchEvent(new CustomEvent('company-address-updated', {
                                             detail: {
                                                 companyName: this.companyName,
                                                 companyAdditional: this.companyAdditional,
                                                 companyStreet: this.companyStreet,
                                                 companyHouseNumber: this.companyHouseNumber,
                                                 companyPostalCode: this.companyPostalCode,
                                                 companyCity: this.companyCity,
                                                 companyCountry: this.companyCountry
                                             }
                                         }));
                                     }
                                 } catch (error) {
                                     console.error('Fehler beim Speichern:', error);
                                 }
                             }
                         }"
                         @company-address-updated.window="
                             companyName = $event.detail.companyName;
                             companyAdditional = $event.detail.companyAdditional;
                             companyStreet = $event.detail.companyStreet;
                             companyHouseNumber = $event.detail.companyHouseNumber;
                             companyPostalCode = $event.detail.companyPostalCode;
                             companyCity = $event.detail.companyCity;
                             companyCountry = $event.detail.companyCountry;
                         ">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Firmenadresse
                            </h3>
                            <button @click="isEditing = !isEditing"
                                    class="p-1 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Anzeigemodus -->
                        <div x-show="!isEditing">
                            <div x-show="companyName">
                                <p class="text-sm text-gray-700">
                                    <strong x-text="companyName"></strong>
                                </p>
                                <p x-show="companyAdditional" x-text="companyAdditional" class="text-sm text-gray-600"></p>
                                <p x-show="companyStreet || companyHouseNumber" class="text-sm text-gray-700 mt-1">
                                    <span x-text="companyStreet"></span>
                                    <span x-show="companyHouseNumber" x-text="' ' + companyHouseNumber"></span>
                                </p>
                                <p x-show="companyPostalCode || companyCity" class="text-sm text-gray-700">
                                    <span x-text="companyPostalCode"></span> <span x-text="companyCity"></span>
                                </p>
                                <p x-show="companyCountry" x-text="companyCountry" class="text-sm text-gray-700"></p>
                            </div>
                            <p x-show="!companyName" class="text-sm text-gray-500 italic">Keine Firmenadresse hinterlegt</p>
                        </div>

                        <!-- Bearbeitungsmodus -->
                        <div x-show="isEditing" class="space-y-3">
                            <input type="text" x-model="companyName" placeholder="Firmenname" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <input type="text" x-model="companyAdditional" placeholder="Zusatz" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" x-model="companyStreet" placeholder="Straße" class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="companyHouseNumber" placeholder="Nr." class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" x-model="companyPostalCode" placeholder="PLZ" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="companyCity" placeholder="Stadt" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <input type="text" x-model="companyCountry" placeholder="Land" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="flex gap-2 pt-2">
                                <button @click="saveCompanyAddress()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">Speichern</button>
                                <button @click="isEditing = false" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition-colors">Abbrechen</button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200"
                         x-data="{
                             isEditing: false,
                             billingCompanyName: '{{ auth('customer')->user()->billing_company_name ?? '' }}',
                             billingAdditional: '{{ auth('customer')->user()->billing_additional ?? '' }}',
                             billingStreet: '{{ auth('customer')->user()->billing_street ?? '' }}',
                             billingHouseNumber: '{{ auth('customer')->user()->billing_house_number ?? '' }}',
                             billingPostalCode: '{{ auth('customer')->user()->billing_postal_code ?? '' }}',
                             billingCity: '{{ auth('customer')->user()->billing_city ?? '' }}',
                             billingCountry: '{{ auth('customer')->user()->billing_country ?? '' }}',
                             async saveBillingAddress() {
                                 try {
                                     const response = await fetch('{{ route('customer.profile.update-billing-address') }}', {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                             'Accept': 'application/json'
                                         },
                                         body: JSON.stringify({
                                             billing_company_name: this.billingCompanyName,
                                             billing_additional: this.billingAdditional,
                                             billing_street: this.billingStreet,
                                             billing_house_number: this.billingHouseNumber,
                                             billing_postal_code: this.billingPostalCode,
                                             billing_city: this.billingCity,
                                             billing_country: this.billingCountry
                                         })
                                     });
                                     const data = await response.json();
                                     if (data.success) {
                                         this.isEditing = false;
                                         window.dispatchEvent(new CustomEvent('billing-address-updated', {
                                             detail: {
                                                 billingCompanyName: this.billingCompanyName,
                                                 billingAdditional: this.billingAdditional,
                                                 billingStreet: this.billingStreet,
                                                 billingHouseNumber: this.billingHouseNumber,
                                                 billingPostalCode: this.billingPostalCode,
                                                 billingCity: this.billingCity,
                                                 billingCountry: this.billingCountry
                                             }
                                         }));
                                     }
                                 } catch (error) {
                                     console.error('Fehler beim Speichern:', error);
                                 }
                             }
                         }"
                         @billing-address-updated.window="
                             billingCompanyName = $event.detail.billingCompanyName;
                             billingAdditional = $event.detail.billingAdditional;
                             billingStreet = $event.detail.billingStreet;
                             billingHouseNumber = $event.detail.billingHouseNumber;
                             billingPostalCode = $event.detail.billingPostalCode;
                             billingCity = $event.detail.billingCity;
                             billingCountry = $event.detail.billingCountry;
                         ">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Rechnungsadresse
                            </h3>
                            <button @click="isEditing = !isEditing"
                                    class="p-1 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Anzeigemodus -->
                        <div x-show="!isEditing">
                            <div x-show="billingCompanyName">
                                <p class="text-sm text-gray-700">
                                    <strong x-text="billingCompanyName"></strong>
                                </p>
                                <p x-show="billingAdditional" x-text="billingAdditional" class="text-sm text-gray-600"></p>
                                <p x-show="billingStreet || billingHouseNumber" class="text-sm text-gray-700 mt-1">
                                    <span x-text="billingStreet"></span>
                                    <span x-show="billingHouseNumber" x-text="' ' + billingHouseNumber"></span>
                                </p>
                                <p x-show="billingPostalCode || billingCity" class="text-sm text-gray-700">
                                    <span x-text="billingPostalCode"></span> <span x-text="billingCity"></span>
                                </p>
                                <p x-show="billingCountry" x-text="billingCountry" class="text-sm text-gray-700"></p>
                            </div>
                            <p x-show="!billingCompanyName" class="text-sm text-gray-500 italic">Keine Rechnungsadresse hinterlegt</p>
                        </div>

                        <!-- Bearbeitungsmodus -->
                        <div x-show="isEditing" class="space-y-3">
                            <input type="text" x-model="billingCompanyName" placeholder="Firmenname" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <input type="text" x-model="billingAdditional" placeholder="Zusatz" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" x-model="billingStreet" placeholder="Straße" class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="billingHouseNumber" placeholder="Nr." class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" x-model="billingPostalCode" placeholder="PLZ" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="billingCity" placeholder="Stadt" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <input type="text" x-model="billingCountry" placeholder="Land" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <div class="flex gap-2 pt-2">
                                <button @click="saveBillingAddress()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">Speichern</button>
                                <button @click="isEditing = false" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300 transition-colors">Abbrechen</button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Status
                        </h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Profil:</span>
                                @if(auth('customer')->user()->customer_type)
                                    <span class="px-2 py-1 bg-green-50 text-green-700 text-xs font-medium rounded border border-green-200">
                                        Vollständig
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-yellow-50 text-yellow-700 text-xs font-medium rounded border border-yellow-200">
                                        Unvollständig
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">E-Mail:</span>
                                @if(auth('customer')->user()->hasVerifiedEmail())
                                    <span class="px-2 py-1 bg-green-50 text-green-700 text-xs font-medium rounded border border-green-200">
                                        Verifiziert
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-yellow-50 text-yellow-700 text-xs font-medium rounded border border-yellow-200">
                                        Nicht verifiziert
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Konto:</span>
                                <span class="px-2 py-1 bg-green-50 text-green-700 text-xs font-medium rounded border border-green-200">
                                    Aktiv
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Schnittstellen
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Passolution</span>
                                @if(auth('customer')->user()->hasActivePassolution())
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('customer.passolution.disconnect') }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 underline"
                                                    onclick="return confirm('Möchten Sie die Passolution-Integration wirklich deaktivieren?')">
                                                Trennen
                                            </button>
                                        </form>
                                        <span class="px-3 py-1 bg-green-50 text-green-700 text-xs font-medium rounded border border-green-200">
                                            Aktiv
                                        </span>
                                    </div>
                                @else
                                    <a href="{{ route('customer.passolution.authorize') }}"
                                       class="px-3 py-1 bg-white text-gray-700 text-xs font-medium rounded border border-gray-300 hover:bg-gray-50 transition-colors inline-block">
                                        Aktivieren
                                    </a>
                                @endif
                            </div>

                            @if(auth('customer')->user()->hasActivePassolution())
                                @php
                                    $customer = auth('customer')->user();
                                    $passolutionService = app(\App\Services\PassolutionService::class);
                                @endphp

                                @if($customer->passolution_subscription_type)
                                    <div class="pt-2 border-t border-gray-200">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs text-gray-600">Abonnement:</span>
                                            <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded border border-blue-200">
                                                {{ ucfirst($customer->passolution_subscription_type) }}
                                            </span>
                                        </div>

                                        @if($customer->passolution_features && count($customer->passolution_features) > 0)
                                            <div class="mt-2">
                                                <span class="text-xs text-gray-600 block mb-2">Freigeschaltete Features:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($passolutionService->getFeatureLabels($customer->passolution_features) as $featureLabel)
                                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded border border-gray-300">
                                                            {{ $featureLabel }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200"
                         x-data="{
                             isActive: {{ auth('customer')->user()->directory_listing_active ? 'true' : 'false' }},
                             async toggleListing() {
                                 try {
                                     const response = await fetch('{{ route('customer.profile.toggle-directory-listing') }}', {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                             'Accept': 'application/json'
                                         },
                                         body: JSON.stringify({ active: !this.isActive })
                                     });
                                     const data = await response.json();
                                     if (data.success) {
                                         this.isActive = data.directory_listing_active;
                                         window.dispatchEvent(new CustomEvent('directory-listing-updated', {
                                             detail: { active: data.directory_listing_active }
                                         }));
                                     }
                                 } catch (error) {
                                     console.error('Fehler beim Speichern:', error);
                                     alert('Fehler beim Speichern. Bitte versuchen Sie es erneut.');
                                 }
                             }
                         }">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Adressverzeichnis
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Wenn Sie im Adressverzeichnis <b>kostenlos</b> gelistet sein möchten, kann dies hier aktiviert werden.
                        </p>
                        <div>
                            <button @click="toggleListing()"
                                    :class="isActive ? 'bg-yellow-400 text-gray-900 border-yellow-500 hover:bg-yellow-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="px-3 py-1 text-xs font-medium rounded border transition-colors">
                                <span x-text="isActive ? 'Deaktivieren' : 'Aktivieren'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200"
                         x-data="{
                             showBox: {{ auth('customer')->user()->directory_listing_active ? 'true' : 'false' }},
                             selectedPlan: null,
                             plans: [
                                 { days: 7, price: 9, label: '7 Tage / 9 EUR' },
                                 { days: 30, price: 25, label: '30 Tage / 25 EUR' },
                                 { days: 90, price: 69, label: '90 Tage / 69 EUR' }
                             ],
                             selectPlan(index) {
                                 this.selectedPlan = index;
                             }
                         }"
                         x-show="showBox"
                         x-transition
                         @directory-listing-updated.window="showBox = $event.detail.active">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Adresse hervorheben
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Meine Adresse kostenpflichtig hervorheben.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(plan, index) in plans" :key="index">
                                <button @click="selectPlan(index)"
                                        :class="selectedPlan === index ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors"
                                        x-text="plan.label">
                                </button>
                            </template>
                        </div>
                        <div x-show="selectedPlan !== null" class="mt-4 pt-4 border-t border-gray-200">
                            <button class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                Jetzt buchen
                            </button>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg border border-gray-200"
                         x-data="{
                             isActive: {{ auth('customer')->user()->branch_management_active ? 'true' : 'false' }},
                             async toggleBranchManagement() {
                                 try {
                                     const response = await fetch('{{ route('customer.profile.toggle-branch-management') }}', {
                                         method: 'POST',
                                         headers: {
                                             'Content-Type': 'application/json',
                                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                             'Accept': 'application/json'
                                         },
                                         body: JSON.stringify({ active: !this.isActive })
                                     });
                                     const data = await response.json();
                                     if (data.success) {
                                         this.isActive = data.branch_management_active;
                                         window.dispatchEvent(new CustomEvent('branch-management-updated', {
                                             detail: { active: data.branch_management_active }
                                         }));
                                         // Seite neu laden, damit das Firmensymbol in der Navigation angezeigt wird
                                         window.location.reload();
                                     }
                                 } catch (error) {
                                     console.error('Fehler beim Speichern:', error);
                                     alert('Fehler beim Speichern. Bitte versuchen Sie es erneut.');
                                 }
                             }
                         }">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            Filialen & Standorte
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Verwalten Sie zugehörige Filialen und Standorte.
                        </p>
                        <div>
                            <button @click="toggleBranchManagement()"
                                    :class="isActive ? 'bg-yellow-400 text-gray-900 border-yellow-500 hover:bg-yellow-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="px-3 py-1 text-xs font-medium rounded border transition-colors">
                                <span x-text="isActive ? 'Deaktivieren' : 'Integrieren'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<style>
    [x-cloak] { display: none !important; }
    .sidebar {
        position: fixed;
        left: 64px;
        top: 64px;
        bottom: 56px;
        width: 320px;
        background: #f9fafb;
        border-right: 1px solid #e5e7eb;
        overflow-y: auto;
        z-index: 50;
    }
    .main-content > .p-8 {
        margin-left: 320px;
    }
    #branches-map {
        height: 400px;
        border-radius: 8px;
        z-index: 1;
    }
    /* Modal Container */
    .modal-overlay {
        z-index: 9998 !important;
    }
    .modal-backdrop {
        z-index: 9998 !important;
    }
    .modal-content {
        z-index: 9999 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
function branchManager() {
    return {
        showModal: false,
        showImportModal: false,
        isDragging: false,
        selectedFile: null,
        selectedFileName: '',
        branches: [],
        form: {
            name: '',
            street: '',
            house_number: '',
            postal_code: '',
            city: '',
            country: 'Deutschland'
        },
        map: null,
        markers: [],
        markerClusterGroup: null,

        init() {
            this.loadBranches();
            this.initMap();

            // Event-Listener für Karte zentrieren
            window.addEventListener('recenter-map', () => {
                this.recenterMap();
            });

            // Event-Listener für Zoom auf Hauptsitz
            window.addEventListener('zoom-to-hq', () => {
                this.zoomToHeadquarters();
            });

            // Event-Listener für Zoom auf Filiale
            window.addEventListener('zoom-to-branch', (e) => {
                this.zoomToBranch(e.detail.id);
            });
        },

        recenterMap() {
            if (!this.map) return;

            // Wenn Marker vorhanden sind, auf diese zentrieren
            if (this.markers.length > 0 && this.markerClusterGroup) {
                const bounds = this.markerClusterGroup.getBounds();
                if (bounds.isValid()) {
                    this.map.fitBounds(bounds.pad(0.1));
                }
            } else {
                // Sonst DACH-Region anzeigen
                const dachBounds = [
                    [47.0, 5.8],   // Südwest (Schweiz)
                    [55.1, 15.0]   // Nordost (Deutschland)
                ];
                this.map.fitBounds(dachBounds);
            }
        },

        async zoomToHeadquarters() {
            if (!this.map) return;

            @if(auth('customer')->user()->company_street)
            const hqAddress = '{{ auth('customer')->user()->company_street }} {{ auth('customer')->user()->company_house_number }}, {{ auth('customer')->user()->company_postal_code }} {{ auth('customer')->user()->company_city }}, {{ auth('customer')->user()->company_country ?? "Deutschland" }}';
            try {
                const hqCoords = await this.geocodeAddress(hqAddress);
                if (hqCoords.lat && hqCoords.lon) {
                    this.map.setView([hqCoords.lat, hqCoords.lon], 15);

                    // Popup öffnen
                    const hqMarker = this.markers.find(m => m.getLatLng().lat === parseFloat(hqCoords.lat));
                    if (hqMarker) {
                        hqMarker.openPopup();
                    }
                }
            } catch (error) {
                console.error('Error zooming to HQ:', error);
            }
            @endif
        },

        zoomToBranch(branchId) {
            if (!this.map) return;

            const branch = this.branches.find(b => b.id === branchId);
            if (branch && branch.latitude && branch.longitude) {
                this.map.setView([parseFloat(branch.latitude), parseFloat(branch.longitude)], 15);

                // Popup öffnen
                const marker = this.markers.find(m => {
                    const lat = m.getLatLng().lat;
                    const lng = m.getLatLng().lng;
                    return Math.abs(lat - parseFloat(branch.latitude)) < 0.0001 &&
                           Math.abs(lng - parseFloat(branch.longitude)) < 0.0001;
                });

                if (marker) {
                    marker.openPopup();
                }
            }
        },

        async loadBranches() {
            try {
                const response = await fetch('/customer/branches');
                const data = await response.json();
                if (data.success) {
                    this.branches = data.branches;
                    this.renderBranches();
                    this.updateMap();
                }
            } catch (error) {
                console.error('Error loading branches:', error);
            }
        },

        renderBranches() {
            const list = document.getElementById('branches-list');
            const countEl = document.getElementById('branches-count');
            if (!list) return;

            let html = '';

            // Headquarters - mit Klick-Funktion und grünem Rahmen
            html += `<div class="bg-white p-3 rounded-lg border-2 border-green-500 cursor-pointer hover:bg-gray-50 transition-colors" onclick="window.dispatchEvent(new CustomEvent('zoom-to-hq'))">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-semibold text-gray-900 text-sm">Hauptsitz</h4>
                            <span class="px-2 py-0.5 bg-gray-200 text-gray-600 text-xs font-mono rounded" title="App-Code">HQ00</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">
                            {{ auth('customer')->user()->company_street }} {{ auth('customer')->user()->company_house_number }}<br>
                            {{ auth('customer')->user()->company_postal_code }} {{ auth('customer')->user()->company_city }}
                        </p>
                    </div>
                </div>
            </div>`;

            // Branches - mit Klick-Funktion
            this.branches.forEach(branch => {
                html += `<div class="bg-white p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors" onclick="window.dispatchEvent(new CustomEvent('zoom-to-branch', { detail: { id: ${branch.id} } }))">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-semibold text-gray-900 text-sm">${branch.name}</h4>
                                <span class="px-2 py-0.5 bg-gray-200 text-gray-600 text-xs font-mono rounded" title="App-Code">${branch.app_code || 'N/A'}</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-1">
                                ${branch.street} ${branch.house_number || ''}<br>
                                ${branch.postal_code} ${branch.city}
                            </p>
                        </div>
                        <button onclick="event.stopPropagation(); deleteBranch(${branch.id})" class="text-red-600 hover:text-red-800 text-xs ml-2">
                            <i class="fa-regular fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            });

            if (this.branches.length === 0) {
                html += `<div class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300 text-center">
                    <i class="fa-regular fa-building text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-500">Keine weiteren Filialen</p>
                </div>`;
            }

            list.innerHTML = html;

            // Update count: 1 Hauptsitz + Anzahl Filialen
            const totalCount = 1 + this.branches.length;
            if (countEl) {
                countEl.textContent = `(${totalCount})`;
            }
        },

        initMap() {
            setTimeout(() => {
                const mapEl = document.getElementById('branches-map');
                if (!mapEl || this.map) return; // Verhindere doppelte Initialisierung

                // Initialisiere Karte mit Bounds für DACH-Region (Deutschland, Österreich, Schweiz)
                this.map = L.map('branches-map');

                // Definiere die Bounds für DACH-Region
                const dachBounds = [
                    [47.0, 5.8],   // Südwest (Schweiz)
                    [55.1, 15.0]   // Nordost (Deutschland)
                ];

                this.map.fitBounds(dachBounds);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(this.map);

                // Initialisiere Marker Cluster Group
                this.markerClusterGroup = L.markerClusterGroup({
                    chunkedLoading: true,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    maxClusterRadius: 50
                });

                this.map.addLayer(this.markerClusterGroup);

                this.updateMap();
            }, 500);
        },

        async updateMap() {
            if (!this.map || !this.markerClusterGroup) return;

            // Clear existing markers from cluster group
            this.markerClusterGroup.clearLayers();
            this.markers = [];

            // Add HQ marker - geocode the headquarters address
            @if(auth('customer')->user()->company_street)
            const hqAddress = '{{ auth('customer')->user()->company_street }} {{ auth('customer')->user()->company_house_number }}, {{ auth('customer')->user()->company_postal_code }} {{ auth('customer')->user()->company_city }}, {{ auth('customer')->user()->company_country ?? "Deutschland" }}';
            try {
                const hqCoords = await this.geocodeAddress(hqAddress);
                if (hqCoords.lat && hqCoords.lon) {
                    const hqMarker = L.marker([hqCoords.lat, hqCoords.lon], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    })
                        .bindPopup(`<b>Hauptsitz</b><br>{{ auth('customer')->user()->company_name }}<br>${hqAddress}`);

                    this.markerClusterGroup.addLayer(hqMarker);
                    this.markers.push(hqMarker);
                }
            } catch (error) {
                console.error('Error geocoding HQ:', error);
            }
            @endif

            // Add branch markers
            this.branches.forEach(branch => {
                if (branch.latitude && branch.longitude) {
                    const marker = L.marker([branch.latitude, branch.longitude], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    })
                        .bindPopup(`<b>${branch.name}</b><br>${branch.street} ${branch.house_number || ''}<br>${branch.postal_code} ${branch.city}`);

                    this.markerClusterGroup.addLayer(marker);
                    this.markers.push(marker);
                }
            });

            // Fit bounds if there are markers
            if (this.markers.length > 0) {
                const bounds = this.markerClusterGroup.getBounds();
                if (bounds.isValid()) {
                    this.map.fitBounds(bounds.pad(0.1));
                }
            }
        },

        async geocodeAddress(address) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1`);
                const data = await response.json();
                if (data.length > 0) {
                    return {
                        lat: parseFloat(data[0].lat),
                        lon: parseFloat(data[0].lon)
                    };
                }
            } catch (error) {
                console.error('Geocoding error:', error);
            }
            return { lat: null, lon: null };
        },

        openModal() {
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.form = { name: '', street: '', house_number: '', postal_code: '', city: '', country: 'Deutschland' };
        },

        async saveBranch() {
            try {
                const response = await fetch('/customer/branches', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (data.success) {
                    this.closeModal();
                    this.loadBranches();
                }
            } catch (error) {
                console.error('Error saving branch:', error);
            }
        },

        exportBranches() {
            // Erstelle CSV-Export
            let csv = 'Name,Zusatz,Straße,Hausnummer,PLZ,Stadt,Land,App-Code\n';

            this.branches.forEach(branch => {
                csv += `"${branch.name}","${branch.additional || ''}","${branch.street}","${branch.house_number || ''}","${branch.postal_code}","${branch.city}","${branch.country}","${branch.app_code}"\n`;
            });

            // Download als CSV-Datei
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'filialen_export_' + new Date().toISOString().slice(0,10) + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        openImportModal() {
            this.showImportModal = true;
            this.selectedFile = null;
            this.selectedFileName = '';
            this.isDragging = false;
        },

        closeImportModal() {
            this.showImportModal = false;
            this.selectedFile = null;
            this.selectedFileName = '';
            this.isDragging = false;
        },

        handleFileDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.setSelectedFile(files[0]);
            }
        },

        handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                this.setSelectedFile(files[0]);
            }
        },

        setSelectedFile(file) {
            if (!file.name.endsWith('.csv')) {
                alert('Bitte wählen Sie eine CSV-Datei aus.');
                return;
            }
            this.selectedFile = file;
            this.selectedFileName = file.name;
        },

        async processImport() {
            if (!this.selectedFile) return;

            const reader = new FileReader();
            reader.onload = async (event) => {
                const csv = event.target.result;
                const lines = csv.split('\n');

                let imported = 0;

                // Überspringe Header-Zeile
                for (let i = 1; i < lines.length; i++) {
                    const line = lines[i].trim();
                    if (!line) continue;

                    // Parse CSV-Zeile
                    const values = line.match(/(".*?"|[^",]+)(?=\s*,|\s*$)/g);
                    if (!values || values.length < 6) continue;

                    const cleanValue = (val) => val ? val.replace(/^"|"$/g, '').trim() : '';

                    const branchData = {
                        name: cleanValue(values[0]),
                        additional: cleanValue(values[1]),
                        street: cleanValue(values[2]),
                        house_number: cleanValue(values[3]),
                        postal_code: cleanValue(values[4]),
                        city: cleanValue(values[5]),
                        country: cleanValue(values[6]) || 'Deutschland'
                    };

                    // Speichere Filiale
                    try {
                        const response = await fetch('/customer/branches', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(branchData)
                        });

                        if (response.ok) {
                            imported++;
                        }
                    } catch (error) {
                        console.error('Error importing branch:', error);
                    }
                }

                // Lade Filialen neu
                await this.loadBranches();
                this.closeImportModal();
                alert(`Import abgeschlossen! ${imported} Filiale(n) wurden importiert.`);
            };
            reader.readAsText(this.selectedFile);
        },

        importBranches() {
            this.openImportModal();
        }
    };
}

async function deleteBranch(id) {
    if (!confirm('Möchten Sie diese Filiale wirklich löschen?')) return;

    try {
        const response = await fetch(`/customer/branches/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();
        if (data.success) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error deleting branch:', error);
    }
}
</script>
@endpush
