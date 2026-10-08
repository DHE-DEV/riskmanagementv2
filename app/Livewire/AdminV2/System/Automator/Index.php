<?php

namespace App\Livewire\AdminV2\System\Automator;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * System > Automator: die Warteschlange der Hintergrund-Jobs. Zeigt, ob der
 * Worker laeuft, welche Jobs warten und welche fehlgeschlagen sind – und
 * laesst Jobs verarbeiten, wiederholen oder entfernen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Automator')]
class Index extends Component
{
    use AuthorizesAdminV2;

    /** Hoechstens so viele Jobs je Reiter */
    public const LIMIT = 100;

    /** pending | failed */
    #[Url(except: 'pending')]
    public string $tab = 'pending';

    /** @var array<int, string> IDs der angehakten wartenden Jobs */
    public array $selectedPending = [];

    /** @var array<int, string> UUIDs der angehakten fehlgeschlagenen Jobs */
    public array $selectedFailed = [];

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            'pending' => 'Wartende Jobs',
            'failed' => 'Fehlgeschlagen',
        ];
    }

    public function updatedTab(): void
    {
        $this->selectedPending = [];
        $this->selectedFailed = [];
    }

    /**
     * Zahlen fuer die Navigation: wartende und fehlgeschlagene Jobs.
     *
     * @return array{pending: int, failed: int}
     */
    public static function counts(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    }

    /**
     * @return array{pending: int, failed: int, processing: int, queues: array<string, int>}
     */
    #[Computed]
    public function stats(): array
    {
        return self::counts() + [
            'processing' => DB::table('jobs')->whereNotNull('reserved_at')->count(),
            'queues' => DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->orderBy('queue')
                ->pluck('count', 'queue')
                ->map(fn ($count) => (int) $count)
                ->all(),
        ];
    }

    /**
     * Laeuft ein Worker (queue:work) auf diesem Server?
     */
    #[Computed]
    public function workerRunning(): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }

        $output = @shell_exec('pgrep -f "queue:work" 2>/dev/null');

        return trim((string) $output) !== '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function pendingJobs(): array
    {
        return DB::table('jobs')
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($job) => [
                'id' => (int) $job->id,
                'queue' => $job->queue,
                'name' => $this->jobName($job->payload),
                'attempts' => (int) $job->attempts,
                'created_at' => Carbon::createFromTimestamp($job->created_at)->format('d.m.Y H:i:s'),
                'available_at' => Carbon::createFromTimestamp($job->available_at)->format('d.m.Y H:i:s'),
                'reserved' => $job->reserved_at !== null,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function failedJobs(): array
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($job) => [
                'id' => (int) $job->id,
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'name' => $this->jobName($job->payload),
                'failed_at' => Carbon::parse($job->failed_at)->format('d.m.Y H:i:s'),
                'exception' => Str::limit(Str::before((string) $job->exception, "\n"), 300),
            ])
            ->all();
    }

    protected function jobName(?string $payload): string
    {
        $data = json_decode((string) $payload, true) ?: [];
        $name = $data['displayName'] ?? $data['job'] ?? 'Unbekannt';

        return class_basename((string) $name);
    }

    // ------------------------------------------------------------------
    // Verarbeiten
    // ------------------------------------------------------------------

    public function processNext(): void
    {
        if (self::counts()['pending'] === 0) {
            $this->toast('Keine Jobs in der Warteschlange.', 'danger');

            return;
        }

        Artisan::call('queue:work', ['--once' => true, '--tries' => 3]);

        $this->refresh();
        $this->toast('Job verarbeitet.');
    }

    public function processAll(): void
    {
        if (self::counts()['pending'] === 0) {
            $this->toast('Keine Jobs in der Warteschlange.', 'danger');

            return;
        }

        Artisan::call('queue:work', ['--stop-when-empty' => true, '--tries' => 3]);

        $this->refresh();
        $this->toast('Alle Jobs wurden verarbeitet.');
    }

    // ------------------------------------------------------------------
    // Wartende Jobs
    // ------------------------------------------------------------------

    public function deletePending(int $jobId): void
    {
        DB::table('jobs')->where('id', $jobId)->delete();

        $this->selectedPending = array_values(array_diff($this->selectedPending, [(string) $jobId]));
        $this->refresh();
        $this->toast('Job aus der Warteschlange entfernt.');
    }

    public function togglePendingPage(): void
    {
        $ids = array_map(fn (array $job) => (string) $job['id'], $this->pendingJobs);

        $this->selectedPending = $ids !== [] && array_diff($ids, $this->selectedPending) === [] ? [] : $ids;
    }

    public function deleteSelectedPending(): void
    {
        $ids = array_values(array_filter(array_map('intval', $this->selectedPending)));

        if ($ids === []) {
            return;
        }

        $removed = DB::table('jobs')->whereIn('id', $ids)->delete();

        $this->selectedPending = [];
        $this->refresh();
        $this->toast($removed === 1 ? '1 Job aus der Warteschlange entfernt.' : $removed.' Jobs aus der Warteschlange entfernt.');
    }

    // ------------------------------------------------------------------
    // Fehlgeschlagene Jobs
    // ------------------------------------------------------------------

    public function retry(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        $this->selectedFailed = array_values(array_diff($this->selectedFailed, [$uuid]));
        $this->refresh();
        $this->toast('Job wird erneut versucht.');
    }

    public function deleteFailed(string $uuid): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        $this->selectedFailed = array_values(array_diff($this->selectedFailed, [$uuid]));
        $this->refresh();
        $this->toast('Fehlgeschlagener Job gelöscht.');
    }

    public function toggleFailedPage(): void
    {
        $uuids = array_map(fn (array $job) => (string) $job['uuid'], $this->failedJobs);

        $this->selectedFailed = $uuids !== [] && array_diff($uuids, $this->selectedFailed) === [] ? [] : $uuids;
    }

    public function retrySelectedFailed(): void
    {
        $uuids = $this->selectedUuids();

        if ($uuids === []) {
            return;
        }

        Artisan::call('queue:retry', ['id' => $uuids]);

        $this->selectedFailed = [];
        $this->refresh();
        $this->toast(count($uuids) === 1 ? '1 Job wird erneut versucht.' : count($uuids).' Jobs werden erneut versucht.');
    }

    public function deleteSelectedFailed(): void
    {
        $uuids = $this->selectedUuids();

        if ($uuids === []) {
            return;
        }

        foreach ($uuids as $uuid) {
            Artisan::call('queue:forget', ['id' => $uuid]);
        }

        $this->selectedFailed = [];
        $this->refresh();
        $this->toast(count($uuids) === 1 ? '1 fehlgeschlagener Job gelöscht.' : count($uuids).' fehlgeschlagene Jobs gelöscht.');
    }

    public function retryAll(): void
    {
        $this->modal('automator-retry-all')->close();

        Artisan::call('queue:retry', ['id' => 'all']);

        $this->selectedFailed = [];
        $this->refresh();
        $this->toast('Alle fehlgeschlagenen Jobs werden erneut versucht.');
    }

    public function flushFailed(): void
    {
        $this->modal('automator-flush')->close();

        Artisan::call('queue:flush');

        $this->selectedFailed = [];
        $this->refresh();
        $this->toast('Alle fehlgeschlagenen Jobs wurden gelöscht.');
    }

    /**
     * Nur UUIDs, die auch in der Liste stehen – die Werte kommen vom Browser.
     *
     * @return array<int, string>
     */
    protected function selectedUuids(): array
    {
        $known = array_map(fn (array $job) => (string) $job['uuid'], $this->failedJobs);

        return array_values(array_intersect(array_map('strval', $this->selectedFailed), $known));
    }

    public function refresh(): void
    {
        unset($this->stats, $this->workerRunning, $this->pendingJobs, $this->failedJobs);
    }

    protected function toast(string $message, string $variant = 'success'): void
    {
        $this->dispatch('adminv2-toast', message: $message, variant: $variant);
    }

    public function render()
    {
        return view('livewire.admin-v2.system.automator.index');
    }
}
