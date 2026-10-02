<?php

namespace App\Mail;

use App\Models\AdminTask;

/**
 * Erinnerung an eine Aufgabe – geht an die fuer die Erinnerung gewaehlte
 * Person, sonst an die, bei der die Aufgabe gerade liegt.
 */
class AdminTaskReminderMail extends AdminTaskMail
{
    public function __construct(
        AdminTask $task,
        public ?string $reminderNote = null,
    ) {
        parent::__construct($task);
    }

    protected function subjectPrefix(): string
    {
        return 'Erinnerung';
    }

    protected function intro(): string
    {
        return 'Erinnerung an eine Aufgabe';
    }

    protected function note(): ?string
    {
        return $this->reminderNote;
    }
}
