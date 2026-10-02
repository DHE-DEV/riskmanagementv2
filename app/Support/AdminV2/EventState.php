<?php

namespace App\Support\AdminV2;

use App\Models\CustomEvent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Der Zustand eines Ereignisses aus Sicht der Redaktion.
 *
 * In der Datenbank ist er auf mehrere Spalten verteilt (is_active, archived,
 * review_status, activated_at, superseded_by_id, Zeitraum). Hier wird daraus
 * genau EIN Zustand – fuer die Anzeige (of) und fuer die Filter der Liste
 * (apply). Beide folgen derselben Rangfolge, damit Badge und Filter nie
 * auseinanderlaufen.
 */
enum EventState: string
{
    case Live = 'live';
    case Scheduled = 'scheduled';
    case Draft = 'draft';
    case Inactive = 'inactive';
    case PendingReview = 'pending';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Archived = 'archived';
    case Superseded = 'superseded';

    public static function of(CustomEvent $event): self
    {
        return match (true) {
            $event->superseded_by_id !== null => self::Superseded,
            $event->review_status === 'pending_review' => self::PendingReview,
            $event->review_status === 'rejected' => self::Rejected,
            (bool) $event->archived => self::Archived,
            ! $event->is_active => $event->activated_at === null ? self::Draft : self::Inactive,
            $event->end_date !== null && $event->end_date->lt(now()->startOfDay()) => self::Expired,
            $event->start_date !== null && $event->start_date->gt(now()) => self::Scheduled,
            default => self::Live,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Live => 'Live',
            self::Scheduled => 'Geplant',
            self::Draft => 'Entwurf',
            self::Inactive => 'Inaktiv',
            self::PendingReview => 'Prüfung ausstehend',
            self::Rejected => 'Abgelehnt',
            self::Expired => 'Abgelaufen',
            self::Archived => 'Archiviert',
            self::Superseded => 'Abgelöst',
        };
    }

    /**
     * Was der Zustand fuer die Auslieferung bedeutet – als Tooltip am Badge.
     */
    public function description(): string
    {
        return match ($this) {
            self::Live => 'Wird auf Karte, in Feeds und in Benachrichtigungen ausgeliefert.',
            self::Scheduled => 'Veröffentlicht, der Zeitraum beginnt aber erst noch.',
            self::Draft => 'Noch nie veröffentlicht – für Kunden unsichtbar.',
            self::Inactive => 'War veröffentlicht und wurde deaktiviert.',
            self::PendingReview => 'Von außen eingereicht, wartet auf Freigabe.',
            self::Rejected => 'Bei der Prüfung abgelehnt.',
            self::Expired => 'Das Enddatum liegt in der Vergangenheit.',
            self::Archived => 'Archiviert – noch ein Jahr nach dem Enddatum sichtbar.',
            self::Superseded => 'Durch eine neuere Version ersetzt, bleibt als Historie erhalten.',
        };
    }

    /**
     * Farbname fuer flux:badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Live => 'green',
            self::Scheduled => 'sky',
            self::Draft => 'indigo',
            self::PendingReview => 'amber',
            self::Rejected => 'red',
            self::Inactive, self::Expired, self::Archived, self::Superseded => 'zinc',
        };
    }

    /**
     * "Aktiv": veroeffentlicht und noch nicht abgelaufen – also Live und
     * Geplant zusammen. Das ist der Bestand, den Karte, Feeds und API ausliefern.
     */
    public static function applyActive(Builder $query): Builder
    {
        return $query
            ->whereNull('superseded_by_id')
            ->where(fn (Builder $q) => $q->whereNull('review_status')
                ->orWhereNotIn('review_status', ['pending_review', 'rejected']))
            ->where(fn (Builder $q) => $q->where('archived', false)->orWhereNull('archived'))
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->startOfDay()));
    }

    /**
     * Die Liste auf diesen Zustand einschraenken.
     */
    public function apply(Builder $query): Builder
    {
        if ($this === self::Superseded) {
            return $query->whereNotNull('superseded_by_id');
        }

        $query->whereNull('superseded_by_id');

        if ($this === self::PendingReview) {
            return $query->where('review_status', 'pending_review');
        }

        if ($this === self::Rejected) {
            return $query->where('review_status', 'rejected');
        }

        $query->where(fn (Builder $q) => $q->whereNull('review_status')
            ->orWhereNotIn('review_status', ['pending_review', 'rejected']));

        if ($this === self::Archived) {
            return $query->where('archived', true);
        }

        // "archived" ist nullable – NULL zaehlt als nicht archiviert.
        $query->where(fn (Builder $q) => $q->where('archived', false)->orWhereNull('archived'));

        return match ($this) {
            self::Draft => $query->where('is_active', false)->whereNull('activated_at'),
            self::Inactive => $query->where('is_active', false)->whereNotNull('activated_at'),
            self::Expired => $query->where('is_active', true)
                ->where('end_date', '<', now()->startOfDay()),
            self::Scheduled => $query->where('is_active', true)
                ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->startOfDay()))
                ->where('start_date', '>', now()),
            default => $query->where('is_active', true)
                ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->startOfDay()))
                ->where(fn (Builder $q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now())),
        };
    }
}
