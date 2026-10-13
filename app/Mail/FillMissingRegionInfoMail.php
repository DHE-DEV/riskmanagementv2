<?php

namespace App\Mail;

use App\Models\RegionInfoRun;
use App\Models\Subregion;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\RegionInfoFill;
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
        $countryIds = (array) ($result['country_ids'] ?? []);

        return new Content(
            view: 'emails.fill-missing-region-info',
            with: [
                'run' => $this->run,
                'result' => $result,
                'created' => Subregion::query()->whereIn('id', (array) ($result['created_ids'] ?? []))->with('region')->withCount('cities')->orderBy('region_id')->orderBy('sort_order')->get(),
                'failures' => RegionInfoFill::failures($this->run),
                'regionsUrl' => route('adminv2.master-data.regions.index', ['info' => RegionInfo::STATUS_AI, 'country' => $countryIds]),
                'subregionsUrl' => route('adminv2.master-data.subregions.index', ['status' => RegionInfo::STATUS_AI, 'country' => $countryIds]),
            ],
        );
    }
}
