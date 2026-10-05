<?php

namespace App\Livewire\AdminV2\CustomerManagement\Customers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;

/**
 * GTM API Logs eines Kunden – reine Anzeige mit Suche, Statusfilter und Sortierung.
 */
trait ListsCustomerGtmLogs
{
    public const LOG_STATUSES = [
        '200' => '200 OK',
        '401' => '401 Unauthorized',
        '403' => '403 Forbidden',
        '404' => '404 Not Found',
        '429' => '429 Too Many Requests',
        '500' => '500 Server Error',
    ];

    public string $logSearch = '';

    public string $logStatus = '';

    public string $logSort = 'created_at';

    public string $logDirection = 'desc';

    /**
     * @return array<string, string>
     */
    public function logSortOptions(): array
    {
        return ['created_at' => 'Zeitpunkt', 'response_time_ms' => 'Antwortzeit'];
    }

    public function updatedLogSearch(): void
    {
        $this->resetPage('logsPage');
    }

    public function updatedLogStatus(): void
    {
        $this->resetPage('logsPage');
    }

    public function sortLogs(string $column): void
    {
        if (! array_key_exists($column, $this->logSortOptions())) {
            return;
        }

        $this->logDirection = $this->logSort === $column && $this->logDirection === 'asc' ? 'desc' : 'asc';
        $this->logSort = $column;
        $this->resetPage('logsPage');
    }

    #[Computed]
    public function gtmLogs(): LengthAwarePaginator
    {
        $query = $this->customer->gtmApiRequestLogs();

        if (($term = trim($this->logSearch)) !== '') {
            $query->where('endpoint', 'like', '%'.addcslashes($term, '%_\\').'%');
        }

        if (isset(self::LOG_STATUSES[$this->logStatus])) {
            $query->where('response_status', (int) $this->logStatus);
        }

        $sort = array_key_exists($this->logSort, $this->logSortOptions()) ? $this->logSort : 'created_at';

        $query
            ->orderBy($sort, $this->logDirection === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id');

        return $this->paginateWithinRange($query, self::RELATION_PER_PAGE, 'logsPage');
    }
}
