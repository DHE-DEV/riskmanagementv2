<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Filters</h3>

    <form method="GET" action="{{ route('admin.sso-logs.index') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Date From -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Date From
                </label>
                <input
                    type="date"
                    name="date_from"
                    id="date_from"
                    value="{{ request('date_from') }}"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <!-- Date To -->
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Date To
                </label>
                <input
                    type="date"
                    name="date_to"
                    id="date_to"
                    value="{{ request('date_to') }}"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Status
                </label>
                <select
                    name="status"
                    id="status"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Statuses</option>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption }}" {{ request('status') == $statusOption ? 'selected' : '' }}>
                            {{ ucfirst($statusOption) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Step -->
            <div>
                <label for="step" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Step
                </label>
                <select
                    name="step"
                    id="step"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Steps</option>
                    @foreach($steps as $stepOption)
                        <option value="{{ $stepOption }}" {{ request('step') == $stepOption ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', ucfirst($stepOption)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Customer ID -->
            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Customer ID
                </label>
                <input
                    type="text"
                    name="customer_id"
                    id="customer_id"
                    value="{{ request('customer_id') }}"
                    placeholder="Enter customer ID"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <!-- Agent ID -->
            <div>
                <label for="agent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Agent ID
                </label>
                <input
                    type="text"
                    name="agent_id"
                    id="agent_id"
                    value="{{ request('agent_id') }}"
                    placeholder="Enter agent ID"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <!-- IP Address -->
            <div>
                <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    IP Address
                </label>
                <input
                    type="text"
                    name="ip_address"
                    id="ip_address"
                    value="{{ request('ip_address') }}"
                    placeholder="Enter IP address"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <!-- Request ID -->
            <div>
                <label for="request_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Request ID
                </label>
                <input
                    type="text"
                    name="request_id"
                    id="request_id"
                    value="{{ request('request_id') }}"
                    placeholder="Enter request ID"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button
                type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
            >
                Apply Filters
            </button>

            <a
                href="{{ route('admin.sso-logs.index') }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
            >
                Clear Filters
            </a>
        </div>
    </form>
</div>
