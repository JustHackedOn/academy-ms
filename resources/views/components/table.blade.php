@props([
    'striped' => false,
])

<div {{ $attributes->merge(['class' => 'w-full overflow-hidden border border-slate-200/80 dark:border-slate-800 rounded-2xl bg-white dark:bg-slate-900 shadow-xs']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
            @if (isset($header))
                <thead class="bg-slate-50/80 dark:bg-slate-800/60 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                    {{ $header }}
                </thead>
            @endif

            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                {{ $slot }}
            </tbody>

            @if (isset($footer))
                <tfoot class="bg-slate-50 dark:bg-slate-800/50 font-medium text-slate-700 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800">
                    {{ $footer }}
                </tfoot>
            @endif
        </table>
    </div>

    @if (isset($pagination))
        <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
            {{ $pagination }}
        </div>
    @endif
</div>
