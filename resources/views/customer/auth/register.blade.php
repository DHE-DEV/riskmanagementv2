@extends('layouts.public')

@section('title', 'Registrierung - Passolution Travel Information Platform')

@section('content')
    <div class="flex items-center justify-center py-10 px-4" style="min-height: calc(100vh - 184px);">
        <div class="flex w-full max-w-xl flex-col gap-6">
            <!-- Card -->
            <div class="flex flex-col gap-6">
                <div class="rounded-xl border bg-white text-stone-800 shadow-lg">
                    <livewire:customer.register-form />
                </div>

                <!-- Login Link -->
                <div class="text-center text-sm text-stone-600">
                    Bereits ein Konto?
                    <a href="{{ route('customer.login') }}" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                        Anmelden
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
