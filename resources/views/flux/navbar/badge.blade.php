@pure

@props([
    'variant' => null,
    'color' => null,
])

@php
$class = Flux::classes()
    ->add('text-xs font-medium rounded-sm px-1 py-0.5')
    /**
     * We can't compile classes for each color because of variants color to color and Tailwind's JIT compiler.
     * We instead need to write out each one by hand. Sorry...
     */
    ->add($variant === 'solid' ? match ($color) {
        default => 'text-white bg-zinc-600',
        'red' => 'text-white bg-red-500',
        'orange' => 'text-white bg-orange-500',
        'amber' => 'text-white bg-amber-500',
        'yellow' => 'text-white bg-yellow-500',
        'lime' => 'text-white bg-lime-500',
        'green' => 'text-white bg-green-500',
        'emerald' => 'text-white bg-emerald-500',
        'teal' => 'text-white bg-teal-500',
        'cyan' => 'text-white bg-cyan-500',
        'sky' => 'text-white bg-sky-500',
        'blue' => 'text-white bg-blue-500',
        'indigo' => 'text-white bg-indigo-500',
        'violet' => 'text-white bg-violet-500',
        'purple' => 'text-white bg-purple-500',
        'fuchsia' => 'text-white bg-fuchsia-500',
        'pink' => 'text-white bg-pink-500',
        'rose' => 'text-white bg-rose-500',
    } :  match ($color) {
        default => 'text-zinc-700 bg-zinc-400/15',
        'red' => 'text-red-700 bg-red-400/20',
        'orange' => 'text-orange-700 bg-orange-400/20',
        'amber' => 'text-amber-700 bg-amber-400/25',
        'yellow' => 'text-yellow-800 bg-yellow-400/25',
        'lime' => 'text-lime-800 bg-lime-400/25',
        'green' => 'text-green-800 bg-green-400/20',
        'emerald' => 'text-emerald-800 bg-emerald-400/20',
        'teal' => 'text-teal-800 bg-teal-400/20',
        'cyan' => 'text-cyan-800 bg-cyan-400/20',
        'sky' => 'text-sky-800 bg-sky-400/20',
        'blue' => 'text-blue-800 bg-blue-400/20',
        'indigo' => 'text-indigo-700 bg-indigo-400/20',
        'violet' => 'text-violet-700 bg-violet-400/20',
        'purple' => 'text-purple-700 bg-purple-400/20',
        'fuchsia' => 'text-fuchsia-700 bg-fuchsia-400/20',
        'pink' => 'text-pink-700 bg-pink-400/20',
        'rose' => 'text-rose-700 bg-rose-400/20',
    });
@endphp

<span {{ $attributes->class($class) }}>{{ $slot }}</span>
