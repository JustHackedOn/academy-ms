@props([
    'name' => 'User',
    'size' => 'md', // sm, md, lg, xl
    'photo' => null,
])

@php
// Generate initials
$words = preg_split("/\s+/", trim($name));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= mb_strtoupper(mb_substr($w, 0, 1));
}
if (empty($initials)) {
    $initials = 'U';
}

// Generate gradient deterministically from name
$gradients = [
    'from-indigo-500 to-purple-600 text-white',
    'from-blue-500 to-indigo-600 text-white',
    'from-emerald-500 to-teal-600 text-white',
    'from-violet-500 to-pink-600 text-white',
    'from-amber-500 to-orange-600 text-white',
    'from-rose-500 to-red-600 text-white',
    'from-cyan-500 to-blue-600 text-white',
];
$hash = crc32($name);
$gradient = $gradients[abs($hash) % count($gradients)];

$sizes = [
    'sm' => 'w-7 h-7 text-xs font-semibold',
    'md' => 'w-9 h-9 text-xs font-bold',
    'lg' => 'w-12 h-12 text-sm font-bold',
    'xl' => 'w-16 h-16 text-lg font-bold',
];

$sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge([
    'class' => "inline-flex items-center justify-center rounded-xl bg-gradient-to-br {$gradient} shadow-xs select-none {$sizeClass}"
]) }} title="{{ $name }}">
    @if ($photo)
        <img src="{{ $photo }}" alt="{{ $name }}" loading="lazy" class="w-full h-full object-cover rounded-xl" />
    @else
        <span>{{ $initials }}</span>
    @endif
</div>
