@pure

@props([
    'color' => null,
])

@php
    $classes = Flux::classes()
        ->add(match ($color) {
            'slate' => '[--color-accent:var(--color-slate-800)] [--color-accent-content:var(--color-slate-800)] [--color-accent-foreground:var(--color-white)]',
            'gray' => '[--color-accent:var(--color-gray-800)] [--color-accent-content:var(--color-gray-800)] [--color-accent-foreground:var(--color-white)]',
            'zinc' => '[--color-accent:var(--color-zinc-800)] [--color-accent-content:var(--color-zinc-800)] [--color-accent-foreground:var(--color-white)]',
            'neutral' => '[--color-accent:var(--color-neutral-800)] [--color-accent-content:var(--color-neutral-800)] [--color-accent-foreground:var(--color-white)]',
            'stone' => '[--color-accent:var(--color-stone-800)] [--color-accent-content:var(--color-stone-800)] [--color-accent-foreground:var(--color-white)]',
            'red' => '[--color-accent:var(--color-red-500)] [--color-accent-content:var(--color-red-600)] [--color-accent-foreground:var(--color-white)]',
            'orange' => '[--color-accent:var(--color-orange-500)] [--color-accent-content:var(--color-orange-600)] [--color-accent-foreground:var(--color-white)]',
            'amber' => '[--color-accent:var(--color-amber-400)] [--color-accent-content:var(--color-amber-600)] [--color-accent-foreground:var(--color-amber-950)]',
            'yellow' => '[--color-accent:var(--color-yellow-400)] [--color-accent-content:var(--color-yellow-600)] [--color-accent-foreground:var(--color-yellow-950)]',
            'lime' => '[--color-accent:var(--color-lime-400)] [--color-accent-content:var(--color-lime-600)] [--color-accent-foreground:var(--color-lime-900)]',
            'green' => '[--color-accent:var(--color-green-600)] [--color-accent-content:var(--color-green-600)] [--color-accent-foreground:var(--color-white)]',
            'emerald' => '[--color-accent:var(--color-emerald-600)] [--color-accent-content:var(--color-emerald-600)] [--color-accent-foreground:var(--color-white)]',
            'teal' => '[--color-accent:var(--color-teal-600)] [--color-accent-content:var(--color-teal-600)] [--color-accent-foreground:var(--color-white)]',
            'cyan' => '[--color-accent:var(--color-cyan-600)] [--color-accent-content:var(--color-cyan-600)] [--color-accent-foreground:var(--color-white)]',
            'sky' => '[--color-accent:var(--color-sky-600)] [--color-accent-content:var(--color-sky-600)] [--color-accent-foreground:var(--color-white)]',
            'blue' => '[--color-accent:var(--color-blue-500)] [--color-accent-content:var(--color-blue-600)] [--color-accent-foreground:var(--color-white)]',
            'indigo' => '[--color-accent:var(--color-indigo-500)] [--color-accent-content:var(--color-indigo-600)] [--color-accent-foreground:var(--color-white)]',
            'violet' => '[--color-accent:var(--color-violet-500)] [--color-accent-content:var(--color-violet-600)] [--color-accent-foreground:var(--color-white)]',
            'purple' => '[--color-accent:var(--color-purple-500)] [--color-accent-content:var(--color-purple-600)] [--color-accent-foreground:var(--color-white)]',
            'fuchsia' => '[--color-accent:var(--color-fuchsia-600)] [--color-accent-content:var(--color-fuchsia-600)] [--color-accent-foreground:var(--color-white)]',
            'pink' => '[--color-accent:var(--color-pink-600)] [--color-accent-content:var(--color-pink-600)] [--color-accent-foreground:var(--color-white)]',
            'rose' => '[--color-accent:var(--color-rose-500)] [--color-accent-content:var(--color-rose-500)] [--color-accent-foreground:var(--color-white)]',
        })
        ;
@endphp

<div {{ $attributes->class($classes) }}>
    {{ $slot }}
</div>