{{--
    Aufgaben zu einem Datensatz – Karte fuer seine Bearbeitungsseite. Erscheint
    nur bei gespeicherten Datensaetzen, an die sich Aufgaben haengen lassen
    (siehe App\Support\AdminV2\TaskSubjects).

    - record: der Datensatz
--}}
@props(['record'])

@php
    $kind = $record?->exists ? \App\Support\AdminV2\TaskSubjects::kindOf($record) : null;
@endphp

@if ($kind)
    <livewire:admin-v2.tasks.panel :kind="$kind" :record-id="$record->getKey()" :key="'tasks-'.$kind.'-'.$record->getKey()" />
@endif
