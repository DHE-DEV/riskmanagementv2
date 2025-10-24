@extends('layouts.public')

@section('title', 'Customer Dashboard - Global Travel Monitor')

@php
    $active = 'dashboard';
@endphp

@section('content')
    <div class="p-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-gray-900">
                        Willkommen, {{ auth('customer')->user()->name }}!
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Das ist Ihr persönliches Dashboard.
                    </p>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        Profil vervollständigen
                    </h2>
                    <p class="text-gray-700 mb-6">
                        Es fehlen nur noch wenige Schritte, bis Ihr Profil vollständig erfasst ist. Um den vollen Funktionsumfang nutzen zu können, führen Sie bitte die ausstehenden Aktionen aus.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6"
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
                             }
                         }">
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
                                         }
                                     } catch (error) {
                                         console.error('Fehler beim Speichern:', error);
                                     }
                                 }
                             }">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-semibold text-gray-900">Firmenanschrift</h3>
                                <span class="px-2 py-1 bg-orange-50 text-orange-700 text-xs font-medium rounded border border-orange-200 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                                    </svg>
                                    Angabe empfohlen
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Bitte geben Sie die Anschrift Ihres Unternehmens ein.</p>
                            <div class="space-y-3">
                                <input type="text" x-model="companyName" @blur="saveCompanyAddress()"
                                       placeholder="Firmenname"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="companyAdditional" @blur="saveCompanyAddress()"
                                       placeholder="Zusatz"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="grid grid-cols-3 gap-3">
                                    <input type="text" x-model="companyStreet" @blur="saveCompanyAddress()"
                                           placeholder="Straße"
                                           class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="companyHouseNumber" @blur="saveCompanyAddress()"
                                           placeholder="Nr."
                                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" x-model="companyPostalCode" @blur="saveCompanyAddress()"
                                           placeholder="PLZ"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="companyCity" @blur="saveCompanyAddress()"
                                           placeholder="Stadt"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="relative">
                                    <input type="text"
                                           x-model="companyCountry"
                                           @input="filterCountries()"
                                           @keydown="handleCountryKeydown($event)"
                                           @focus="loadCountries(); filterCountries()"
                                           @blur="setTimeout(() => showCountryDropdown = false, 200)"
                                           placeholder="Land"
                                           autocomplete="off"
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
                                         }
                                     } catch (error) {
                                         console.error('Fehler beim Speichern:', error);
                                     }
                                 }
                             }">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-semibold text-gray-900">Rechnungsadresse</h3>
                                <span class="px-2 py-1 bg-green-50 text-green-700 text-xs font-medium rounded border border-green-200 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Freiwillige Angabe
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">Bitte geben Sie Ihre Rechnungsadresse ein.</p>
                            <div class="space-y-3">
                                <input type="text" x-model="billingCompanyName" @blur="saveBillingAddress()"
                                       placeholder="Firmenname"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" x-model="billingAdditional" @blur="saveBillingAddress()"
                                       placeholder="Zusatz"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <div class="grid grid-cols-3 gap-3">
                                    <input type="text" x-model="billingStreet" @blur="saveBillingAddress()"
                                           placeholder="Straße"
                                           class="col-span-2 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="billingHouseNumber" @blur="saveBillingAddress()"
                                           placeholder="Nr."
                                           class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" x-model="billingPostalCode" @blur="saveBillingAddress()"
                                           placeholder="PLZ"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" x-model="billingCity" @blur="saveBillingAddress()"
                                           placeholder="Stadt"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="relative">
                                    <input type="text"
                                           x-model="billingCountry"
                                           @input="filterBillingCountries()"
                                           @keydown="handleBillingCountryKeydown($event)"
                                           @focus="loadCountries(); filterBillingCountries()"
                                           @blur="setTimeout(() => showBillingCountryDropdown = false, 200)"
                                           placeholder="Land"
                                           autocomplete="off"
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
                </div>

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
            </div>
        </div>
    </div>
@endsection
