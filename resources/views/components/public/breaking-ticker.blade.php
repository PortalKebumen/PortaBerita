@if ($items->isNotEmpty())
    {{-- Teks berjalan: daftar diulang dua kali agar loop mulus, salinan kedua disembunyikan dari pembaca layar --}}
    <div class="breaking-ticker border-b border-[#CDD3DF] bg-white" role="region" aria-label="Breaking news">
        <div class="mx-auto flex max-w-7xl items-stretch px-4 sm:px-6 lg:px-8">
            <span class="relative z-10 flex shrink-0 items-center gap-2 bg-accent-500 py-2 pl-3 pr-4 text-[11px] font-bold uppercase tracking-wide text-white sm:text-xs">
                <span class="relative flex h-2 w-2">
                    <span class="breaking-ticker__ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                </span>
                <span class="hidden sm:inline">Breaking News</span>
                <span class="sm:hidden">Breaking</span>
            </span>

            <div class="breaking-ticker__viewport relative min-w-0 flex-1 overflow-hidden">
                <div class="breaking-ticker__track flex w-max items-center py-2"
                     style="--ticker-duration: {{ max(25, $items->count() * 12) }}s">
                    @foreach ([false, true] as $isClone)
                        <ul class="flex shrink-0 items-center" @if ($isClone) aria-hidden="true" @endif>
                            @foreach ($items as $item)
                                <li class="flex shrink-0 items-center gap-2 pl-6 text-[13px]">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-accent-500"></span>
                                    <a href="{{ $item->url }}" @if ($isClone) tabindex="-1" @endif
                                       class="font-semibold text-[#171B28] hover:text-brand-600 hover:underline">{{ $item->title }}</a>
                                    @if ($item->published_at)
                                        <time datetime="{{ $item->published_at }}" class="text-[11.5px] text-[#848CA3]">
                                            {{ \Illuminate\Support\Carbon::parse($item->published_at)->diffForHumans() }}
                                        </time>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
