<?php

namespace App\Mail;

use App\Models\AdminTask;

/**
 * Eine Aufgabe beginnt fuer jemanden: Sie wurde neu angelegt oder an die
 * Person uebergeben.
 */
class AdminTaskAssignedMail extends AdminTaskMail
{
    public function __construct(
        AdminTask $task,
        public bool $isNew = true,
        public ?string $assignedBy = null,
    ) {
        parent::__construct($task);
    }

    protected function subjectPrefix(): string
    {
        return $this->isNew ? 'Neue Aufgabe' : 'Aufgabe für dich';
    }

    protected function intro(): string
    {
        $by = $this->assignedBy ? ' von '.$this->assignedBy : '';

        return $this->isNew
            ? 'Neue Aufgabe für dich'.$by
            : 'Diese Aufgabe wurde dir'.$by.' übergeben';
    }
}
