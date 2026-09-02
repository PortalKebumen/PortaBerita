@props(['label' => null])

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-accent-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-accent-600']) }}>
    {{ $label ?? $slot }}
</span>