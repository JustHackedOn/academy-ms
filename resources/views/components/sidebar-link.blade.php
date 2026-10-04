@props([
    'active' => false,
    'href' => '#',
    'badge' => null,
])

@php
$classes = $active
    ? 'sidebar-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold bg-indigo-600 text-white shadow-sm shadow-indigo-500/20 transition-all duration-200'
    : 'sidebar-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/60 transition-all duration-200';
@endphp

<a href="{{ $href }}" title="{{ $title }}" {{ $attributes->merge(['class' => $classes]) }}>
    <div class="w-5 h-5 flex items-center justify-center flex-shrink-0">
        {{ $slot }}
    </div>

    <span class="sidebar-hide truncate flex-1">{{ $title }}</span>

    @if ($badge)
        <span
            class="sidebar-hide px-2 py-0.5 text-xs font-semibold rounded-full {{ $active ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}"
        >
            {{ $badge }}
        </span>
    @endif
</a>
