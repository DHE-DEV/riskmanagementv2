<?php

namespace App\Livewire\AdminV2;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AiFieldReview;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "KI-Auftraege" im Menue: die eigenen Feldpruefungen der letzten Tage mit
 * ihrem Stand. Fertige melden sich per Hinweis und fuehren zum Eintrag,
 * wo sich das Ergebnis oeffnet – egal, auf welcher Seite man gerade ist.
 */
class AiReviewTray extends Component
{
    use AuthorizesAdminV2;

    /** So lange stehen Auftraege in der Liste (Tage). */
    public const DAYS = 3;

    /** @var array<int, int> offene Auftraege beim letzten Abfragen */
    public array $openIds = [];

    public function mount(): void
    {
        $this->openIds = $this->reviews->filter->isOpen()->pluck('id')->all();
    }

    /**
     * @return Collection<int, AiFieldReview>
     */
    #[Computed]
    public function reviews(): Collection
    {
        return AiFieldReview::query()
            ->where('user_id', auth('web')->id())
            ->where('created_at', '>=', now()->subDays(self::DAYS))
            ->latest()
            ->limit(30)
            ->get(['id', 'area', 'section', 'title', 'url', 'status', 'error', 'started_at', 'finished_at', 'seen_at', 'created_at']);
    }

    /**
     * Abfragen: Auftraege, die seit dem letzten Mal fertig wurden, melden sich.
     */
    #[On('adminv2-ai-reviews-changed')]
    public function refresh(): void
    {
        unset($this->reviews);

        foreach ($this->reviews->whereIn('id', $this->openIds) as $review) {
            // Steht das Ergebnis schon im Formular, braucht es keinen Hinweis.
            if ($review->isFinished() && $review->seen_at === null) {
                $this->dispatch('adminv2-toast',
                    message: 'KI-Auftrag fertig: '.trim(($review->title ? $review->title.' › ' : '').$review->sectionLabel()).($review->failureMessage() ? ' – fehlgeschlagen' : ' – unter „KI-Aufträge“ öffnen'),
                    variant: $review->failureMessage() ? 'danger' : 'success',
                );
            }
        }

        $this->openIds = $this->reviews->filter->isOpen()->pluck('id')->all();
    }

    /**
     * Zum Eintrag, dort oeffnet sich das Ergebnis.
     */
    public function open(int $id): void
    {
        $review = AiFieldReview::query()->where('user_id', auth('web')->id())->find($id);

        if (! $review) {
            return;
        }

        if (! $review->openUrl()) {
            $review->update(['seen_at' => now()]);
            unset($this->reviews);

            return;
        }

        $this->redirect($review->openUrl(), navigate: false);
    }

    public function markAllSeen(): void
    {
        AiFieldReview::query()->where('user_id', auth('web')->id())->unseenFinished()->update(['seen_at' => now()]);
        unset($this->reviews);
    }

    public function render()
    {
        return view('livewire.admin-v2.ai-review-tray', [
            'openCount' => $this->reviews->filter->isOpen()->count(),
            'unseenCount' => $this->reviews->filter(fn (AiFieldReview $review) => $review->isFinished() && $review->seen_at === null)->count(),
        ]);
    }
}
