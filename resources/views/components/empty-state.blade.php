@props([
    'title' => 'No items found',
    'description' => 'There are no records to display at this time.',
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center p-8 sm:p-12 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30']) }}>
    <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
        @if (isset($customIcon))
            {{ $customIcon }}
        @else
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
        @endif
    </div>

    <h4 class="text-base font-semibold text-slate-900 dark:text-white">
        {{ $title }}
    </h4>

    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm">
        {{ $description }}
    </p>

    @if (isset($action))
        <div class="mt-5">
            {{ $action }}
        </div>
    @endif
</div>
