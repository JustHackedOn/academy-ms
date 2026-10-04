@props([
    'title' => null,
    'subtitle' => null,
    'hover' => true,
    'padding' => 'p-6',
    'headerBorder' => false,
])

<div {{ $attributes->merge([
    'class' => 'bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl md:rounded-3xl shadow-sm transition-all duration-300 ' .
    ($hover ? 'hover:-translate-y-0.5 hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 ' : '') .
    $padding
]) }}>
    @if ($title || isset($header) || isset($action))
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-4 {{ $headerBorder ? 'pb-4 border-b border-slate-100 dark:border-slate-800' : '' }}">
            <div>
                @if ($title)
                    <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white tracking-tight">
                        {{ $title }}
                    </h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ $subtitle }}
                    </p>
                @endif
                {{ $header ?? '' }}
            </div>

            @if (isset($action))
                <div class="flex flex-wrap items-center gap-2">
                    {{ $action }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if (isset($footer))
        <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            {{ $footer }}
        </div>
    @endif
</div>
