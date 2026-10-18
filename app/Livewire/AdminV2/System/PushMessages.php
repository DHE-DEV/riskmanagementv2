<?php

namespace App\Livewire\AdminV2\System;

use App\Jobs\Push\FanOutPushMessage;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTravelLink;
use App\Models\Push\AppPushMessage;
use App\Services\Push\PushDispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * System > Push senden: eigene Push-Benachrichtigung an die TripData-App – an alle Geraete,
 * die eine der angegebenen Reisen (Reise-ID eines Travel Detail Links) gespeichert haben, oder
 * an alle Geraete. Gleicher Weg wie POST /api/v1/push/messages (FanOutPushMessage), Quelle "admin".
 * Darunter die letzten Versaende mit ihrem Stand.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Push senden')]
class PushMessages extends Component
{
    use AuthorizesAdminV2;

    public const TARGET_TIDS = 'tids';

    public const TARGET_ALL = 'all';

    /** Hoechstens so viele Reise-IDs je Versand (wie in der API). */
    public const MAX_TIDS = 1000;

    public string $target = self::TARGET_TIDS;

    /** Reise-IDs, getrennt durch Zeilen, Kommas oder Leerzeichen. */
    public string $tidsInput = '';

    /** @var array<string, string> Titel je Sprache */
    public array $title = ['de' => '', 'en' => '', 'nl' => ''];

    /** @var array<string, string> Text je Sprache */
    public array $body = ['de' => '', 'en' => '', 'nl' => ''];

    /** Bei „alle Geraete“: ausdruecklich bestaetigt. */
    public bool $confirmAll = false;

    public const LANGUAGES = ['de' => 'Deutsch', 'en' => 'Englisch', 'nl' => 'Niederländisch'];

    /** @return array{valid: array<int, string>, invalid: array<int, string>} */
    #[Computed]
    public function parsedTids(): array
    {
        $valid = [];
        $invalid = [];
        foreach (preg_split('/[\s,;]+/', $this->tidsInput, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
            // Ganze Links mit ?tid=… oder /reise/… gehen auch.
            if (preg_match('~(?:[?&]tid=|/reise/)([A-Za-z0-9-]+)~', $entry, $m)) {
                $entry = $m[1];
            }
            $tid = AppDeviceTravelLink::normalizeTid($entry);
            $tid ? $valid[$tid] = $tid : $invalid[] = $entry;
        }

        return ['valid' => array_values($valid), 'invalid' => array_values(array_unique($invalid))];
    }

    /** Geraete, die der Versand gerade erreichen wuerde (erlaubt, gueltiger Token, in den letzten 180 Tagen gemeldet). */
    #[Computed]
    public function reach(): int
    {
        $query = AppDevice::reachable()->where('app', 'tripdata');

        if ($this->target === self::TARGET_ALL) {
            return $query->count();
        }

        $tids = $this->parsedTids['valid'];
        if ($tids === []) {
            return 0;
        }

        return $query->whereHas('travelLinks', fn (Builder $q) => $q->whereIn('tid', $tids))->count();
    }

    /** @return Collection<int, AppPushMessage> */
    #[Computed]
    public function messages(): Collection
    {
        return AppPushMessage::query()->with('user:id,name')->latest('id')->limit(25)->get();
    }

    public function updatedTarget(): void
    {
        $this->confirmAll = false;
        $this->resetValidation();
    }

    public function send(PushDispatcher $dispatcher): void
    {
        $this->validate([
            'title.de' => ['required', 'string', 'max:120'],
            'body.de' => ['required', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:120'],
            'body.en' => ['nullable', 'string', 'max:500'],
            'title.nl' => ['nullable', 'string', 'max:120'],
            'body.nl' => ['nullable', 'string', 'max:500'],
        ], [
            'title.de.required' => 'Bitte einen deutschen Titel eingeben.',
            'body.de.required' => 'Bitte einen deutschen Text eingeben.',
        ]);

        $tids = $this->parsedTids['valid'];
        if ($this->target === self::TARGET_TIDS) {
            if ($tids === []) {
                $this->addError('tidsInput', 'Bitte mindestens eine gültige Reise-ID eingeben.');

                return;
            }
            if (count($tids) > self::MAX_TIDS) {
                $this->addError('tidsInput', 'Höchstens '.number_format(self::MAX_TIDS, 0, ',', '.').' Reise-IDs auf einmal.');

                return;
            }
        } elseif (! $this->confirmAll) {
            $this->addError('confirmAll', 'Bitte bestätigen, dass die Benachrichtigung an alle Geräte gehen soll.');

            return;
        }

        if ($this->reach === 0) {
            $this->addError('target', 'Kein Gerät erreichbar – es wurde nichts gesendet.');

            return;
        }

        // Nur ausgefuellte Sprachen; ein Geraet ohne passende Sprache bekommt Deutsch.
        $languages = array_filter(array_keys(self::LANGUAGES), fn ($lang) => trim($this->title[$lang] ?? '') !== '' && trim($this->body[$lang] ?? '') !== '');

        $message = $dispatcher->begin([
            'app' => 'tripdata',
            'type' => AppPushMessage::TYPE_CUSTOM,
            'source' => AppPushMessage::SOURCE_ADMIN,
            'user_id' => Auth::guard('web')->id(),
            'title' => collect($languages)->mapWithKeys(fn ($lang) => [$lang => trim($this->title[$lang])])->all(),
            'body' => collect($languages)->mapWithKeys(fn ($lang) => [$lang => trim($this->body[$lang])])->all(),
            'target' => $this->target === self::TARGET_ALL ? ['all' => true] : ['tids' => $tids],
        ]);

        FanOutPushMessage::dispatch($message->id);

        $reach = $this->reach;
        $this->reset('tidsInput', 'title', 'body', 'confirmAll');
        unset($this->messages, $this->reach, $this->parsedTids);

        $this->dispatch('adminv2-toast', message: $reach === 1
            ? 'Push wird an 1 Gerät gesendet.'
            : 'Push wird an '.number_format($reach, 0, ',', '.').' Geräte gesendet.');
    }

    public function render()
    {
        return view('livewire.admin-v2.system.push-messages');
    }
}
