<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="relative">
        <div class="fi-input-wrp">
            <div class="fi-input-wrp-content-ctn">
                <input
                    {{ $attributes->merge($getExtraInputAttributes(), escape: false)->class([
                        'fi-input',
                        'pr-10',
                    ]) }}
                    type="url"
                    {!! $isDisabled() ? 'disabled' : null !!}
                    wire:model="{{ $getStatePath() }}"
                />
            </div>
        </div>

        @if($getState())
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <a
                    href="{{ $getState() }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pointer-events-auto flex items-center text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition"
                    onclick="event.stopPropagation()"
                >
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            </div>
        @endif
    </div>
</x-dynamic-component>
