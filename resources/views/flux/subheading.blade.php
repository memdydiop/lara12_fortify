@pure

@props([
    'size' => 'base',
])

@php
$classes = Flux::classes()
    ->add(match ($size) {
        'xl' => 'text-lg',
        'lg' => 'text-base',
        default => 'text-sm',
        'sm' => 'text-xs',
    })
    ->add('[:where(&)]:text-zinc-500')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-subheading>
    {{ $slot }}
</div>
