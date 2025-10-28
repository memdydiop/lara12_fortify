@pure

@props([
    'inline' => false,
    'variant' => null,
    'color' => null,
    'size' => null,
])

@php
$classes = Flux::classes()
    ->add(match ($size) {
        'xl' => 'text-lg',
        'lg' => 'text-base',
        default => '[:where(&)]:text-sm',
        'sm' => 'text-xs',
    })
    ->add($color ? match($color) {
        'red' => 'text-red-600',
        'orange' => 'text-orange-600',
        'amber' => 'text-amber-600',
        'yellow' => 'text-yellow-600',
        'lime' => 'text-lime-600',
        'green' => 'text-green-600',
        'emerald' => 'text-emerald-600',
        'teal' => 'text-teal-600',
        'cyan' => 'text-cyan-600',
        'sky' => 'text-sky-600',
        'blue' => 'text-blue-600',
        'indigo' => 'text-indigo-600',
        'violet' => 'text-violet-600',
        'purple' => 'text-purple-600',
        'fuchsia' => 'text-fuchsia-600',
        'pink' => 'text-pink-600',
        'rose' => 'text-rose-600',
    } : match ($variant) {
        'strong' => '[:where(&)]:text-zinc-800',
        'subtle' => '[:where(&)]:text-zinc-400',
        default => '[:where(&)]:text-zinc-500',
    })
    ;
@endphp
{{-- NOTE: It's important that this file has NO newline at the end of the file. --}}
<?php if ($inline) : ?><span {{ $attributes->class($classes) }} data-flux-text @if ($color) color="{{ $color }}" @endif>{{ $slot }}</span><?php else: ?><p {{ $attributes->class($classes) }} data-flux-text @if ($color) data-color="{{ $color }}" @endif>{{ $slot }}</p><?php endif; ?>