@php $checks = $this->seoChecks; $passed = collect($checks)->where('ok', true)->count(); @endphp
<div class="border border-[#E4E8EF] rounded-lg mb-5 p-4">
    <div class="flex items-baseline justify-between gap-3">
        <h3 class="text-[13px] font-semibold text-[#171B28]">Periksa SEO</h3>
        <span class="text-[12px] text-[#6C7387]">{{ $passed }} dari {{ count($checks) }} terpenuhi</span>
    </div>
    <ul class="mt-2 space-y-1.5">
        @foreach ($checks as $check)
            <li class="flex gap-2 text-[12.5px] leading-snug">
                <span class="w-4 shrink-0 font-bold {{ $check['ok'] ? 'text-success' : 'text-[#B5730A]' }}">{{ $check['ok'] ? '✓' : '!' }}</span>
                <span>
                    <span class="{{ $check['ok'] ? 'text-[#3B4152]' : 'text-[#171B28] font-medium' }}">{{ $check['label'] }}</span>
                    @unless ($check['ok'])
                        <span class="block text-[11.5px] text-[#6C7387]">{{ $check['hint'] }}</span>
                    @endunless
                </span>
            </li>
        @endforeach
    </ul>
    <p class="mt-2 text-[11px] text-[#848CA3]">Panduan saja, tidak menghalangi penyimpanan. Diperbarui saat judul, ringkasan, atau tag diubah, dan saat artikel disimpan.</p>
</div>
