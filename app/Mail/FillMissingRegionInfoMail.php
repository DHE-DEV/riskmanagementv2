<?php

namespace App\Mail;

use App\Models\Region;
use App\Models\RegionInfoRun;
use App\Models\Subregion;
use App\Support\AdminV2\RegionInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ergebnis eines Laufs von regions:fill-missing – an die beim Start
 * genannten Adressen, mit Links zu den neuen KI-Entwuerfen.
 */
class FillMissingRegionInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RegionInfoRun $run,
    ) {}

    public function envelope(): Envelope
    {
        $countries = implode(', ', (array) ($this->run->result['countries'] ?? [])) ?: 'alle Länder';

        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: 'KI-Ergänzung der Regionsinfos ('.$countries.'): '.($this->run->status === RegionInfoRun::STATUS_DONE ? 'fertig' : 'abgebrochen'),
        );
    }

    public function content(): Content
    {
        $result = (array) $this->run->result;
        $failed = (array) $this->run->failed;
        $countryIds = (array) ($result['country_ids'] ?? []);

        // Namen der fehlgeschlagenen Eintraege fuer die Liste
        $ids = fn (string $type) => array_map(fn ($key) => (int) substr($key, 1), array_filter(array_keys($failed), fn ($key) => $key[0] === $type));
        $names = Region::withTrashed()->whereIn('id', [...$ids('r'), ...$ids('d')])->get()->flatMap(fn ($region) => ['r'.$region->id => $region->getName('de'), 'd'.$region->id => $region->getName('de').' (Suche nach Teilgebieten)'])->all()
            + Subregion::withTrashed()->whereIn('id', $ids('s'))->get()->mapWithKeys(fn ($subregion) => ['s'.$subregion->id => $subregion->getName('de').' ('.$subregion->kindLabel().')'])->all();

        return new Content(
            view: 'emails.fill-missing-region-info',
            with: [
                'run' => $this->run,
                'result' => $result,
                'created' => Subregion::query()->whereIn('id', (array) ($result['created_ids'] ?? []))->with('region')->withCount('cities')->orderBy('region_id')->orderBy('sort_order')->get(),
                'failures' => collect($failed)->map(fn ($message, $key) => ['name' => $names[$key] ?? $key, 'message' => $message])->values(),
                'regionsUrl' => route('adminv2.master-data.regions.index', ['info' => RegionInfo::STATUS_AI, 'country' => $countryIds]),
                'subregionsUrl' => route('adminv2.master-data.subregions.index', ['status' => RegionInfo::STATUS_AI, 'country' => $countryIds]),
            ],
        );
    }
}
