<?php

namespace App\Mail;

use App\Models\PluginClient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PluginKeyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PluginClient $pluginClient
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihr Global Travel Monitor Plugin-Zugang',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plugin-key',
            with: [
                'companyName' => $this->pluginClient->company_name,
                'contactName' => $this->pluginClient->contact_name,
                'publicKey' => $this->pluginClient->activeKey?->public_key,
                'domains' => $this->pluginClient->domains->pluck('domain')->toArray(),
                'embedSnippet' => $this->pluginClient->getEmbedSnippet(),
                'dashboardUrl' => route('plugin.dashboard'),
            ],
        );
    }
}
