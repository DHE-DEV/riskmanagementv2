{{-- Kurze Rueckmeldung nach einer Aktion: per Livewire-Event "adminv2-toast"
     oder – nach einer Weiterleitung – aus der Session. --}}
<div
    x-data="{
        toasts: [],
        add(message, variant = 'success') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, variant });
            setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), variant === 'danger' ? 8000 : 4000);
        },
    }"
    x-init="@if (session('adminv2-toast')) add(@js(session('adminv2-toast'))) @endif"
    x-on:adminv2-toast.window="add($event.detail.message, $event.detail.variant ?? 'success')"
    class="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex flex-col items-center gap-2 px-4"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition.opacity.duration.200ms
            class="pointer-events-auto flex max-w-xl items-start gap-3 rounded-xl px-4 py-3 text-sm font-medium shadow-lg ring-1"
            :class="toast.variant === 'danger'
                ? 'bg-red-600 text-white ring-red-700'
                : 'bg-zinc-900 text-white ring-zinc-800 dark:bg-white dark:text-zinc-900 dark:ring-zinc-200'"
        >
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
