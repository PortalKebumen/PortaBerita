@props(['label' => 'Advertisement', 'height' => 'h-32'])

<div {{ $attributes->merge(['class' => "flex $height w-full items-center justify-center rounded-card border border-dashed border-[#CDD3DF] bg-[#F1F3F7] text-xs font-semibold uppercase tracking-wide text-[#848CA3]"]) }}>
    {{ $label }}
</div>