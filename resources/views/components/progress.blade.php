@props([
    'value' => 0, // 0 - 100
    'variant' => 'indigo', // indigo, emerald, amber, rose, violet
    'size' => 'md', // sm, md, lg
    'showLabel' => false,
])

@php
$clamped = max(0, min(100, (float)$value));

$barVariants = [
    'indigo' => 'bg-indigo-600 dark:bg-indigo-500',
    'emerald' => 'bg-emerald-500 dark:bg-emerald-400',
    'amber' => 'bg-amber-500 dark:bg-amber-400',
    'rose' => 'bg-rose-500 dark:bg-rose-400',
    'violet' => 'bg-violet-600 dark:bg-violet-500',
];

$heights = [
    'sm' => 'h-1.5',
    'md' => 'h-2.5',
    'lg' => 'h-4',
];

$barColor = $barVariants[$variant] ?? $barVariants['indigo'];
$height = $heights[$size] ?? $heights['md'];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($showLabel)
        <div class="flex justify-between items-center mb-1 text-xs font-semibold num-tabular text-slate-600 dark:text-slate-300">
            <span>Progress</span>
            <span>{{ number_format($clamped, 0) }}%</span>
        </div>
    @endif
    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden {{ $height }}">
        <div
            class="{{ $barColor }} {{ $height }} rounded-full transition-all duration-500 ease-out"
            style="width: {{ $clamped }}%"
            role="progressbar"
            aria-valuenow="{{ $clamped }}"
            aria-valuemin="0"
            aria-valuemax="100"
        ></div>
    </div>
</div>
