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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="block p-4 bg-white rounded-lg border border-gray-200"
                             x-data="{
                                 customerType: '{{ auth('customer')->user()->customer_type ?? '' }}',
                                 saving: false,
                                 async updateCustomerType(type) {
                                     console.log('Updating customer type to:', type);
                                     this.customerType = type;
                                     this.saving = true;

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
                                             // Dispatch event für Profil-Update
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
                                 }
                             }">
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
                        <a href="https://stage.global-travel-monitor.eu/entry-conditions" class="block p-4 bg-white rounded-lg hover:shadow-md transition border border-gray-200">
                            <h3 class="font-semibold text-gray-900">Einreisebestimmungen</h3>
                            <p class="text-sm text-gray-600">Bestimmungen prüfen</p>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-white p-6 rounded-lg border border-gray-200"
                         x-data="{
                             customerTypeLabel: '{{ auth('customer')->user()->customer_type ? (auth('customer')->user()->customer_type === 'business' ? 'Firmenkunde' : 'Privatkunde') : 'Nicht festgelegt' }}'
                         }"
                         @customer-type-updated.window="
                             console.log('Event received in profile:', $event.detail);
                             customerTypeLabel = $event.detail.label;
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
                        <p class="text-xs text-gray-700 mt-2">
                            Kundentyp: <span x-text="customerTypeLabel"></span>
                        </p>
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
