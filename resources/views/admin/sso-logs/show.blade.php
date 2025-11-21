<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Log Details - {{ $requestId }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .timeline-step {
            position: relative;
            padding-left: 3rem;
        }
        .timeline-step::before {
            content: '';
            position: absolute;
            left: 0.875rem;
            top: 2.5rem;
            bottom: -1rem;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-step:last-child::before {
            display: none;
        }
        .timeline-dot {
            position: absolute;
            left: 0.375rem;
            top: 0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 9999px;
        }
    </style>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">SSO Admin</h1>
                        </div>
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="{{ route('admin.sso-logs.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                Logs
                            </a>
                            <a href="{{ route('admin.sso-logs.stats') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                Statistics
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <div class="mb-6">
                    <a href="{{ route('admin.sso-logs.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                        &larr; Back to Logs
                    </a>
                </div>

                <!-- Header -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">SSO Request Details</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Request ID: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $requestId }}</code>
                    </p>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Steps</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $logs->count() }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Duration</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalDuration }}ms</div>
                    </div>
                    @if($customer)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Customer</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $customer->name }}</div>
                        </div>
                    @endif
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Agent ID</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $firstLog->agent_id ?? 'N/A' }}</div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Timeline</h3>

                    <div class="space-y-6">
                        @foreach($logs as $log)
                            <div class="timeline-step">
                                <!-- Status Dot -->
                                <div class="timeline-dot
                                    @if($log->status === 'success') bg-green-500
                                    @elseif($log->status === 'error') bg-red-500
                                    @elseif($log->status === 'warning') bg-yellow-500
                                    @else bg-blue-500
                                    @endif
                                "></div>

                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                                    {{ str_replace('_', ' ', ucfirst($log->step)) }}
                                                </h4>

                                                <!-- Status Badge -->
                                                @if($log->status === 'success')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Success
                                                    </span>
                                                @elseif($log->status === 'error')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                        Error
                                                    </span>
                                                @elseif($log->status === 'warning')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                        Warning
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        Info
                                                    </span>
                                                @endif

                                                @if($log->duration_ms)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $log->duration_ms }}ms
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                                {{ $log->message }}
                                            </p>

                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $log->created_at->format('Y-m-d H:i:s.u') }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Error Details -->
                                    @if($log->error_message)
                                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                                            <h5 class="text-sm font-semibold text-red-900 dark:text-red-200 mb-1">Error</h5>
                                            <p class="text-sm text-red-800 dark:text-red-300">{{ $log->error_message }}</p>
                                        </div>
                                    @endif

                                    <!-- Stack Trace -->
                                    @if($log->stack_trace)
                                        <details class="mt-4">
                                            <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                Stack Trace
                                            </summary>
                                            <pre class="mt-2 p-3 bg-gray-900 text-gray-100 rounded text-xs overflow-x-auto">{{ $log->stack_trace }}</pre>
                                        </details>
                                    @endif

                                    <!-- Request Data -->
                                    @if($log->request_data)
                                        <details class="mt-4">
                                            <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                Request Data
                                            </summary>
                                            <pre class="mt-2 p-3 bg-gray-900 text-gray-100 rounded text-xs overflow-x-auto">{{ json_encode($log->request_data, JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @endif

                                    <!-- Response Data -->
                                    @if($log->response_data)
                                        <details class="mt-4">
                                            <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                Response Data
                                            </summary>
                                            <pre class="mt-2 p-3 bg-gray-900 text-gray-100 rounded text-xs overflow-x-auto">{{ json_encode($log->response_data, JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @endif

                                    <!-- JWT/Additional Data -->
                                    @if($log->data)
                                        <details class="mt-4">
                                            <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                Additional Data
                                            </summary>
                                            <pre class="mt-2 p-3 bg-gray-900 text-gray-100 rounded text-xs overflow-x-auto">{{ json_encode($log->data, JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
