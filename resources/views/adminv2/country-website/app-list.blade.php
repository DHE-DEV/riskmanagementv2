{{-- Taxi-Apps bzw. Mobilfunkanbieter mit Logo, Beschreibung und Links. --}}
<ul class="grid gap-3">
    @foreach ($items as $item)
        <li class="flex items-start gap-3 rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-800/60">
            <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-1.5 shadow-sm dark:bg-zinc-900">
                @if (! empty($item['logo_url']))<img src="{{ $item['logo_url'] }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy">@else<i class="fa-solid fa-mobile-screen text-zinc-400"></i>@endif
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $item['name'] }}</span>
                    @if (! empty($esim) && ! empty($item['offers_esim']))<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">eSIM</span>@endif
                </div>
                @if (! empty($item['description']))<p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $item['description'] }}</p>@endif
                <div class="mt-2 flex flex-wrap gap-3 text-xs">
                    @foreach (array_filter(['Website' => $item['website_url'] ?? null, 'Prepaid' => $item['prepaid_url'] ?? null, 'App Store' => $item['app_store_url'] ?? null, 'Google Play' => $item['play_store_url'] ?? null]) as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="font-medium text-[#0b3250] hover:underline dark:text-lime-300">{{ $label }} <i class="fa-solid fa-arrow-up-right-from-square ms-0.5 text-[10px]"></i></a>
                    @endforeach
                </div>
            </div>
        </li>
    @endforeach
</ul>
