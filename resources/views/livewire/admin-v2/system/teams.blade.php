@php
    use App\Models\AdminTeam;
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Teams</flux:heading>
            <flux:subheading>System · Teams der Mitarbeiter. Aufgaben können statt einer Person einem Team gehören.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">Neues Team</flux:button>
    </div>

    <div class="grid gap-4 lg:grid-cols-2" wire:loading.class="opacity-60" wire:target="save, delete">
        @forelse ($this->teams as $team)
            @php
                $members = $team->activeMembers();
                $openTasks = $this->openTaskCounts[$team->id] ?? 0;
            @endphp
            <article wire:key="team-{{ $team->id }}" class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold leading-snug text-zinc-900 dark:text-white">{{ $team->name }}</h2>
                        @if ($team->description)
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $team->description }}</p>
                        @endif
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $team->id }})">Bearbeiten</flux:button>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $team->id }})" wire:confirm="Das Team „{{ $team->name }}“ löschen?" aria-label="Team löschen" />
                    </div>
                </div>

                <dl class="mt-3 flex flex-col gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-start gap-2">
                        <dt class="mt-0.5 shrink-0"><flux:icon.user-group variant="mini" class="text-zinc-400" /><span class="sr-only">Mitglieder</span></dt>
                        <dd class="min-w-0">
                            @if ($members->isEmpty())
                                <span class="font-medium text-amber-600 dark:text-amber-400">Noch keine Mitglieder</span>
                            @else
                                <span class="text-zinc-900 dark:text-white">{{ $members->count() }} {{ $members->count() === 1 ? 'Mitglied' : 'Mitglieder' }}:</span>
                                {{ $members->map(fn ($user) => trim($user->name))->sort()->implode(', ') }}
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-start gap-2">
                        <dt class="mt-0.5 shrink-0"><flux:icon.envelope variant="mini" class="text-zinc-400" /><span class="sr-only">Benachrichtigungen</span></dt>
                        <dd class="min-w-0 break-words">
                            @if ($team->usesTeamEmail())
                                Benachrichtigungen an die Team-Adresse <span class="text-zinc-900 dark:text-white">{{ $team->email }}</span>
                            @else
                                Benachrichtigungen an jedes Mitglied einzeln
                                @if ($team->email)
                                    <span class="text-zinc-500">· Team-Adresse {{ $team->email }}</span>
                                @endif
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-auto pt-4 text-xs tabular-nums text-zinc-500">
                    <span @class(['font-medium text-zinc-900 dark:text-white' => $openTasks > 0])>{{ $openTasks }} offene {{ $openTasks === 1 ? 'Aufgabe' : 'Aufgaben' }}</span>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-zinc-300 px-5 py-16 text-center dark:border-zinc-700">
                <div class="mx-auto flex max-w-md flex-col items-center gap-2">
                    <flux:icon.user-group class="size-8 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">Noch keine Teams</p>
                    <p class="text-sm text-zinc-500">Lege ein Team an und ordne Mitarbeiter zu. Danach lässt es sich bei Aufgaben als Verantwortlicher oder nächster Bearbeiter wählen.</p>
                    <flux:button size="sm" icon="plus" wire:click="create" class="mt-1">Neues Team</flux:button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Team anlegen / bearbeiten --}}
    <flux:modal name="team" class="md:w-[36rem]">
        <form wire:submit="save" class="flex flex-col gap-5">
            <flux:heading size="lg">{{ $teamId ? 'Team bearbeiten' : 'Neues Team' }}</flux:heading>

            <flux:input wire:model="name" label="Name" placeholder="z. B. Redaktion" maxlength="100" />
            <flux:input wire:model="description" label="Beschreibung" placeholder="Wofür ist das Team zuständig? (optional)" maxlength="255" />

            <flux:field>
                <flux:label>Mitglieder</flux:label>
                {{-- Liste direkt im Dialog (keine Aufklappliste: sie wuerde vom Dialog abgeschnitten). --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700" x-data="{ query: '' }">
                    <div class="border-b border-zinc-100 p-1.5 dark:border-zinc-800">
                        <input
                            type="search"
                            x-model="query"
                            placeholder="Name suchen"
                            autocomplete="off"
                            aria-label="Mitarbeiter suchen"
                            class="h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm outline-none placeholder:text-zinc-400 focus:border-[var(--color-accent)] dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        />
                    </div>
                    <div class="flex max-h-52 flex-col overflow-y-auto p-1">
                        @foreach ($this->users as $user)
                            <label
                                wire:key="member-option-{{ $user->id }}"
                                x-show="query.trim() === '' || @js(mb_strtolower(trim($user->name))).includes(query.trim().toLowerCase())"
                                class="flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm text-zinc-800 hover:bg-zinc-100 has-[:checked]:font-medium dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                <input type="checkbox" wire:model="memberIds" value="{{ $user->id }}" class="size-4 shrink-0 rounded border-zinc-300 accent-[var(--color-accent)]" />
                                <span class="min-w-0 flex-1 truncate">{{ trim($user->name) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <flux:description>Beliebig viele Mitarbeiter; eine Person kann mehreren Teams angehören.</flux:description>
                <flux:error name="memberIds" />
            </flux:field>

            <flux:input wire:model="email" type="email" label="Zentrale E-Mail-Adresse" placeholder="team@example.com (optional)" maxlength="255" />

            <flux:radio.group wire:model.live="notifyMode" label="Benachrichtigungen gehen an">
                <flux:radio value="{{ AdminTeam::NOTIFY_MEMBERS }}" label="jedes Mitglied einzeln" description="Jede Person bekommt die Mail an ihre eigene Adresse." />
                <flux:radio value="{{ AdminTeam::NOTIFY_TEAM_EMAIL }}" label="die zentrale Adresse des Teams" description="Eine Mail an die Team-Adresse statt an die einzelnen Mitglieder." />
            </flux:radio.group>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Abbrechen</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $teamId ? 'Speichern' : 'Team anlegen' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
