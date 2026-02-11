@extends('layouts.embed')

@section('title', 'Anmeldung - Risiko-Übersicht')
@section('hide_default_badge', true)

@section('additional-styles')
    body {
        overflow: auto;
    }
@endsection

@section('content')
<div class="flex items-center justify-center min-h-screen bg-gray-50 px-4 py-12">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                <i class="fa-regular fa-shield-exclamation text-2xl text-blue-600"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Risiko-Übersicht</h2>
            <p class="mt-2 text-sm text-gray-600">
                Bitte melden Sie sich an, um die Risiko-Übersicht zu sehen.
            </p>
        </div>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5" action="{{ route('embed.risk-overview.login') }}" method="POST">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-Mail-Adresse</label>
                <input id="email" name="email" type="email" autocomplete="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                       placeholder="ihre@email.de"
                       value="{{ old('email') }}">
                @error('email')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Passwort</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                       placeholder="Passwort">
                @error('password')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox"
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-700">
                    Angemeldet bleiben
                </label>
            </div>

            <button type="submit"
                    class="w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Anmelden
            </button>
        </form>
    </div>
</div>
@endsection
