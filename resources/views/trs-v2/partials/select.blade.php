@php
    /**
     * Reusable searchable select used inside the trsSearch() Alpine root.
     *
     * @var string $uid          unique id for open/query state
     * @var string $label
     * @var string $path         dotted path into form state, e.g. "ptd.destinations"
     * @var string $optionsKey   "countries" | "nationalities" | "languages" | "tourOperators"
     * @var bool   $multiple
     */
    $multiple = $multiple ?? false;
    $help = $help ?? null;
    $placeholder = $placeholder ?? __('trs.SelectPlaceholder');
@endphp

<div class="pds-select relative" @click.away="openId === '{{ $uid }}' && (openId = null)">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }}
        @if($help)
            <span class="ml-1 text-gray-400 cursor-help" title="{{ $help }}"><i class="fa-regular fa-circle-question text-xs"></i></span>
        @endif
    </label>

    <button type="button" @click="toggleDropdown('{{ $uid }}')"
            class="w-full flex items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm hover:border-pds-blue focus:outline-none focus:ring-2 focus:ring-pds-green transition"
            :class="openId === '{{ $uid }}' ? 'border-pds-blue ring-2 ring-pds-green' : ''">
        <span class="truncate"
              :class="summary('{{ $path }}', '{{ $optionsKey }}', {{ $multiple ? 'true' : 'false' }}) ? 'text-gray-800' : 'text-gray-400'"
              x-text="summary('{{ $path }}', '{{ $optionsKey }}', {{ $multiple ? 'true' : 'false' }}) || '{{ $placeholder }}'"></span>
        <i class="fa-solid fa-chevron-down text-xs text-gray-400 shrink-0" :class="openId === '{{ $uid }}' ? 'rotate-180' : ''"></i>
    </button>

    @if($multiple)
        {{-- selected chips --}}
        <div class="flex flex-wrap gap-1 mt-1.5" x-show="get('{{ $path }}').length">
            <template x-for="code in get('{{ $path }}')" :key="code">
                <span class="inline-flex items-center gap-1 bg-pds-blue/10 text-pds-blue text-xs rounded-full pl-2 pr-1 py-0.5">
                    <span x-text="label('{{ $optionsKey }}', code)"></span>
                    <button type="button" class="hover:text-red-600" @click="toggleValue('{{ $path }}', code)"><i class="fa-solid fa-xmark"></i></button>
                </span>
            </template>
        </div>
    @endif

    {{-- dropdown panel --}}
    <div x-show="openId === '{{ $uid }}'" x-cloak x-transition.opacity
         class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-xl">
        <div class="p-2 border-b border-gray-100">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" x-model="queries['{{ $uid }}']"
                       placeholder="{{ __('trs.SearchPlaceholder') }}"
                       class="w-full rounded-md border border-gray-200 pl-8 pr-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-pds-green">
            </div>
        </div>
        <ul class="max-h-60 overflow-y-auto pds-scroll py-1">
            <template x-for="opt in filtered('{{ $optionsKey }}', '{{ $uid }}')" :key="opt.code">
                <li>
                    <button type="button"
                            @click="{{ $multiple ? "toggleValue('".$path."', opt.code)" : "setValue('".$path."', opt.code); openId = null" }}"
                            class="w-full flex items-center gap-2 px-3 py-2 text-left text-sm hover:bg-gray-50">
                        @if($multiple)
                            <span class="w-4 h-4 rounded border flex items-center justify-center shrink-0"
                                  :class="has('{{ $path }}', opt.code) ? 'bg-pds-blue border-pds-blue text-white' : 'border-gray-300'">
                                <i class="fa-solid fa-check text-[10px]" x-show="has('{{ $path }}', opt.code)"></i>
                            </span>
                        @else
                            <i class="fa-solid fa-check text-pds-blue text-xs w-4 shrink-0" :class="is('{{ $path }}', opt.code) ? '' : 'invisible'"></i>
                        @endif
                        <span class="truncate" x-text="opt.name"></span>
                        <span class="ml-auto text-[10px] text-gray-400 font-mono" x-text="opt.code"></span>
                    </button>
                </li>
            </template>
            <li x-show="!filtered('{{ $optionsKey }}', '{{ $uid }}').length" class="px-3 py-3 text-sm text-gray-400 text-center">
                {{ __('trs.NoResultsFound') }}
            </li>
        </ul>
    </div>
</div>
