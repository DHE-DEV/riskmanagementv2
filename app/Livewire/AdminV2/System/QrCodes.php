<?php

namespace App\Livewire\AdminV2\System;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Services\QrCode\QrCodeGenerator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * System > QR-Codes: QR-Code erzeugen, auf dem Bildschirm anzeigen und als PDF herunterladen.
 *
 * Art „TripData-Link“ (Vorgabe) macht aus der Reise-ID den Link https://plattform.passolution.de/reise/…:
 * Mit installierter TripData-App oeffnet die Handy-Kamera ihn direkt in der App (Reise oeffnen oder
 * anlegen), ohne App landet er auf der Reisenden-Seite. Art „App-Direktlink“ macht daraus
 * tripdata://reise/… – oeffnet die App ohne Server (Uebergang, solange die Domain nicht bereitsteht),
 * ohne App passiert beim Scannen nichts. Art „Travel Details“ macht aus der Reise-ID eines Travel Detail Links den Link der
 * Reisenden-Seite (https://travel-details.eu/de?tid=…) – den liest die TripData-App beim Scannen,
 * und mit der Handy-Kamera oeffnet er die Reise im Browser. Art „Freier Inhalt“ kodiert Text oder
 * einen beliebigen Link unveraendert.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('QR-Codes')]
class QrCodes extends Component
{
    use AuthorizesAdminV2;

    public const TYPE_TRIPDATA = 'tripdata';

    public const TYPE_TRIPDATA_APP = 'tripdata-app';

    public const TYPE_TRAVEL_DETAILS = 'travel-details';

    public const TYPE_FREE = 'free';

    public string $type = self::TYPE_TRIPDATA;

    /** Reise-ID (Art Travel Details) bzw. Text oder Link (freier Inhalt). */
    public string $value = '';

    public string $language = 'de';

    /** Ueberschrift im PDF (optional). */
    public string $title = '';

    /** Normalisierte Reise-ID, null wenn (noch) keine gueltige eingegeben ist. */
    #[Computed]
    public function travelCode(): ?string
    {
        return in_array($this->type, [self::TYPE_TRIPDATA, self::TYPE_TRIPDATA_APP, self::TYPE_TRAVEL_DETAILS], true)
            ? QrCodeGenerator::normalizeTravelDetailsCode($this->value)
            : null;
    }

    /** Inhalt des QR-Codes; null, solange nichts Gueltiges eingegeben ist. */
    #[Computed]
    public function content(): ?string
    {
        if ($this->type === self::TYPE_TRIPDATA) {
            return $this->travelCode ? QrCodeGenerator::tripDataUrl($this->travelCode) : null;
        }
        if ($this->type === self::TYPE_TRIPDATA_APP) {
            return $this->travelCode ? QrCodeGenerator::tripDataAppUrl($this->travelCode) : null;
        }
        if ($this->type === self::TYPE_TRAVEL_DETAILS) {
            return $this->travelCode ? QrCodeGenerator::travelDetailsUrl($this->travelCode, $this->language) : null;
        }

        $text = trim($this->value);

        return $text === '' ? null : $text;
    }

    /** Hinweis unter dem Eingabefeld, wenn die Eingabe (noch) nicht passt. */
    #[Computed]
    public function inputError(): ?string
    {
        if (trim($this->value) === '') {
            return null;
        }
        if ($this->type !== self::TYPE_FREE && ! $this->travelCode) {
            return 'Keine gültige Reise-ID: Sie hat 12 Zeichen (z. B. ZIPT-HRG1-Z6Y5) oder steckt in einem Link mit „?tid=…“.';
        }
        if ($this->type === self::TYPE_FREE && strlen(trim($this->value)) > 1500) {
            return 'Der Inhalt ist zu lang für einen gut lesbaren QR-Code (höchstens 1.500 Zeichen).';
        }

        return null;
    }

    #[Computed]
    public function svg(): ?string
    {
        return $this->content && ! $this->inputError ? app(QrCodeGenerator::class)->svg($this->content) : null;
    }

    public function updatedType(): void
    {
        $this->resetValidation();
    }

    public function downloadPdf(): ?StreamedResponse
    {
        if (! $this->content || $this->inputError) {
            $this->addError('value', $this->inputError ?? 'Bitte zuerst einen Inhalt eingeben.');

            return null;
        }

        $title = trim($this->title) !== ''
            ? trim($this->title)
            : ($this->travelCode ? 'Reise '.$this->travelCode : 'QR-Code');
        $pdf = app(QrCodeGenerator::class)->pdf($this->content, $title, $this->travelCode);
        $fileName = 'qr-'.($this->travelCode ?: Str::slug(Str::limit($this->content, 40, ''))).'.pdf';

        return response()->streamDownload(fn () => print ($pdf), $fileName, ['Content-Type' => 'application/pdf']);
    }

    public function render()
    {
        return view('livewire.admin-v2.system.qr-codes');
    }
}
