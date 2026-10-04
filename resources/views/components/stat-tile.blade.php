@props([
    'title',
    'value',
    'subtitle' => null,
    'trend' => null,
    'trendType' => 'up', // up, down, neutral
    'variant' => 'default', // default, indigo, emerald, amber, rose, violet, sky
])

@php
$variants = [
    'default' => [
        'bg' => 'bg-white dark:bg-slate-900',
        'border' => 'border-slate-200/80 dark:border-slate-800',
        'iconBg' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    ],
    'indigo' => [
        'bg' => 'bg-gradient-to-br from-indigo-50/50 via-white to-white dark:from-indigo-950/20 dark:via-slate-900 dark:to-slate-900',
        'border' => 'border-indigo-100 dark:border-indigo-900/40',
        'iconBg' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300',
    ],
    'emerald' => [
        'bg' => 'bg-gradient-to-br from-emerald-50/50 via-white to-white dark:from-emerald-950/20 dark:via-slate-900 dark:to-slate-900',
        'border' => 'border-emerald-100 dark:border-emerald-900/40',
        'iconBg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    ],
    'amber' => [
        'bg' => 'bg-gradient-to-br from-amber-50/50 via-white to-white dark:from-amber-950/20 dark:via-slate-900 dark:to-slate-900',
        'border' => 'border-amber-100 dark:border-amber-900/40',
        'iconBg' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    ],
    'rose' => [
        'bg' => 'bg-gradient-to-br from-rose-50/50 via-white to-white dark:from-rose-950/20 dark:via-slate-900 dark:to-slate-900',
        'border' => 'border-rose-100 dark:border-rose-900/40',
        'iconBg' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
    ],
    'sky' => [
        'bg' => 'bg-gradient-to-br from-sky-50/50 via-white to-white dark:from-sky-950/20 dark:via-slate-900 dark:to-slate-900',
        'border' => 'border-sky-100 dark:border-sky-900/40',
        'iconBg' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300',
    ],
    'violet' => [
        'bg' => 'bg-gradient-to-br from-violet-50/50 via-white to-white dark:from-violet-950/20 dark:via-slate-900 dark:to-slate-900',
        'border' => 'border-violet-100 dark:border-violet-900/40',
        'iconBg' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300',
    ],
];

$style = $variants[$variant] ?? $variants['default'];
@endphp

<div {{ $attributes->merge([
    'class' => "relative overflow-hidden {$style['bg']} border {$style['border']} rounded-2xl md:rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300"
]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 truncate">
                {{ $title }}
            </p>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white num-tabular tracking-tight whitespace-nowrap">
                    {{ $value }}
                </span>
                @if ($trend)
                    <span @class([
                        'inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold num-tabular',
                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' => $trendType === 'up',
                        'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300' => $trendType === 'down',
                        'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $trendType === 'neutral',
                    ])>
                        @if ($trendType === 'up')
                            <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" /></svg>
                        @elseif ($trendType === 'down')
                            <svg class="w-3 h-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" /></svg>
                        @endif
                        {{ $trend }}
                    </span>
                @endif
            </div>

            @if ($subtitle)
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if (isset($icon))
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $style['iconBg'] }}">
                {{ $icon }}
            </div>
        @endif
    </div>

    @if (isset($slot) && $slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
