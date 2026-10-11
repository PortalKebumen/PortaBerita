@if ($items->isNotEmpty())
    <div class="border-b border-[#CDD3DF] bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2 sm:px-6 lg:px-8">
            <span class="shrink-0 rounded bg-accent-500 px-2 py-0.5 text-[10.5px] font-bold uppercase tracking-wide text-white">Breaking</span>
            <div class="flex min-w-0 flex-1 items-center gap-6 overflow-x-auto whitespace-nowrap text-[13px] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($items as $item)
                    <a href="{{ $item->url }}" class="shrink-0 font-medium text-[#3B4152] hover:text-brand-600">{{ $item->title }}</a>
                @endforeach
            </div>
        </div>
    </div>
@endif