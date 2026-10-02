<?php

namespace App\Mail;

/**
 * Eine Aufgabe ist faellig und noch nicht erledigt – geht an die Person, bei
 * der sie liegt, und an die verantwortliche Person.
 */
class AdminTaskDueMail extends AdminTaskMail
{
    protected function subjectPrefix(): string
    {
        return 'Fällig';
    }

    protected function intro(): string
    {
        $due = $this->task->due_date;

        return $due && $due->lt(today())
            ? 'Diese Aufgabe ist seit dem '.$due->format('d.m.Y').' fällig und noch nicht erledigt'
            : 'Diese Aufgabe ist heute fällig und noch nicht erledigt';
    }
}
