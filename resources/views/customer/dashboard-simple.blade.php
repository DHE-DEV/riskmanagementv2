@extends('layouts.dashboard-minimal')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Success Message -->
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-bold">✅ SSO Login Successful!</p>
                    <p>You have been successfully authenticated via Single Sign-On from pds-homepage.</p>
                </div>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">
                <i class="fas fa-user-circle mr-2"></i>
                Welcome, {{ auth('customer')->user()->name }}!
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-semibold">{{ auth('customer')->user()->email }}</p>
                </div>

                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm text-gray-600">Customer ID</p>
                    <p class="font-semibold">{{ auth('customer')->user()->id }}</p>
                </div>

                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm text-gray-600">Agent ID</p>
                    <p class="font-semibold">{{ auth('customer')->user()->agent_id ?? 'N/A' }}</p>
                </div>

                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm text-gray-600">Authentication Guard</p>
                    <p class="font-semibold">customer</p>
                </div>
            </div>
        </div>

        <!-- SSO Flow Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-bold mb-3 text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                SSO Flow Details
            </h3>
            <ul class="space-y-2 text-sm text-gray-700">
                <li class="flex items-start">
                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                    <span><strong>Step 1:</strong> Logged in to pds-homepage (http://127.0.0.1:8000)</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                    <span><strong>Step 2:</strong> Clicked "Global Travel Monitor" menu item</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                    <span><strong>Step 3:</strong> JWT created and exchanged for OTT</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                    <span><strong>Step 4:</strong> Redirected to riskmanagementv2 with OTT</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                    <span><strong>Step 5:</strong> User authenticated and session persisted</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                    <span><strong>Step 6:</strong> Landed on dashboard (http://127.0.0.1:8002/customer/dashboard)</span>
                </li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-between">
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </button>
            </form>

            <a href="{{ route('customer.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Go to Full Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
