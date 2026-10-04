@props([
    'size' => 'md', // sm, md, lg, xl
    'flat' => false,
])

@php
    $sizes = [
        'sm' => 'w-7 h-7 text-[11px] rounded-lg',
        'md' => 'w-10 h-10 text-sm rounded-xl',
        'lg' => 'w-14 h-14 text-lg rounded-2xl',
        'xl' => 'w-20 h-20 text-2xl rounded-3xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $logo = setting('academy_logo');
@endphp

@if ($logo)
    <span {{ $attributes->merge(['class' => "inline-flex items-center justify-center overflow-hidden bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 {$sizeClass}"]) }} title="{{ academy_name() }}">
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="{{ academy_name() }}" class="w-full h-full object-contain p-0.5" />
    </span>
@else
    <span
        {{ $attributes->merge([
            'class' => "inline-flex items-center justify-center font-extrabold tracking-tight select-none " .
                ($flat ? 'bg-indigo-600 text-white ' : 'bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-md shadow-indigo-500/25 ') .
                $sizeClass,
        ]) }}
        title="{{ academy_name() }}"
    >{{ academy_initials() }}</span>
@endif
