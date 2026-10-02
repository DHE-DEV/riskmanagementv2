<?php

namespace App\Mail;

use App\Models\AdminTask;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Gemeinsame Grundlage der Mails zu einer Aufgabe aus dem Admin-Bereich:
 * Zuweisung, Faelligkeit und Erinnerung unterscheiden sich nur in Betreff
 * und Einleitung.
 */
abstract class AdminTaskMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdminTask $task,
    ) {}

    /** Steht im Betreff vor dem Titel der Aufgabe. */
    abstract protected function subjectPrefix(): string;

    /** Zeile ueber dem Titel: worum es in dieser Mail geht. */
    abstract protected function intro(): string;

    /** Zusaetzlicher Hinweis in der Mail, z. B. die Notiz einer Erinnerung. */
    protected function note(): ?string
    {
        return null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Passolution Travel Information Platform'),
            subject: $this->subjectPrefix().': '.$this->task->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-task',
            with: [
                'task' => $this->task,
                'intro' => $this->intro(),
                'heading' => $this->subjectPrefix(),
                'note' => $this->note(),
                'url' => route('adminv2.tasks.show', $this->task),
            ],
        );
    }
}
