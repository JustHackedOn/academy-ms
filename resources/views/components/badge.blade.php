@props([
    'status' => 'pending',
    'size' => 'md', // sm, md, lg
    'pulse' => false,
])

@php
$normalized = strtolower(trim((string)$status));

$statusStyles = [
    'paid' => [
        'label' => 'Paid',
        'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60',
        'dot' => 'bg-emerald-500',
    ],
    'cleared' => [
        'label' => 'Cleared',
        'classes' => 'bg-emerald-600 text-white border-transparent shadow-xs dark:bg-emerald-500',
        'dot' => 'bg-white',
    ],
    'partial' => [
        'label' => 'Partial',
        'classes' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60',
        'dot' => 'bg-amber-500',
    ],
    'pending' => [
        'label' => 'Pending',
        'classes' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'dot' => 'bg-slate-400',
    ],
    'overdue' => [
        'label' => 'Overdue',
        'classes' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60',
        'dot' => 'bg-rose-500',
    ],
    'active' => [
        'label' => 'Active',
        'classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60',
        'dot' => 'bg-emerald-500',
    ],
    'running' => [
        'label' => 'Running',
        'classes' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60',
        'dot' => 'bg-indigo-500',
    ],
    'upcoming' => [
        'label' => 'Upcoming',
        'classes' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60',
        'dot' => 'bg-sky-500',
    ],
    'completed' => [
        'label' => 'Completed',
        'classes' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'dot' => 'bg-slate-400',
    ],
    'dropped' => [
        'label' => 'Dropped',
        'classes' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60',
        'dot' => 'bg-rose-500',
    ],
    'paused' => [
        'label' => 'Paused',
        'classes' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60',
        'dot' => 'bg-amber-500',
    ],
    'admin' => [
        'label' => 'Admin',
        'classes' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60',
        'dot' => 'bg-indigo-500',
    ],
    'partner' => [
        'label' => 'Partner',
        'classes' => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800/60',
        'dot' => 'bg-purple-500',
    ],
];

$config = $statusStyles[$normalized] ?? [
    'label' => ucfirst($normalized),
    'classes' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
    'dot' => 'bg-slate-400',
];

$sizeClasses = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-xs',
    'lg' => 'px-3 py-1.5 text-sm',
][$size] ?? 'px-2.5 py-1 text-xs';
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center gap-1.5 rounded-full font-medium border {$sizeClasses} {$config['classes']}"
]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }} {{ ($pulse || $normalized === 'overdue') ? 'animate-pulse' : '' }}"></span>
    <span>{{ $slot->isNotEmpty() ? $slot : $config['label'] }}</span>
</span>
