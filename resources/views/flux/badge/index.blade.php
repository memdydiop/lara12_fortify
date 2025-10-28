@pure

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconVariant' => 'micro',
    'iconTrailing' => null,
    'variant' => null,
    'color' => null,
    'inset' => null,
    'size' => null,
    'icon' => null,
])

@php
$insetClasses = Flux::applyInset($inset, top: '-mt-1', right: '-me-2', bottom: '-mb-1', left: '-ms-2');

// When using the outline icon variant, we need to size it down to match the default icon sizes...
$iconClasses = Flux::classes()->add($iconVariant === 'outline' ? 'size-4' : '');

$classes = Flux::classes()
    ->add('inline-flex items-center font-medium whitespace-nowrap')
    ->add($insetClasses)
    ->add('[print-color-adjust:exact]')
    ->add(match ($size) {
        'lg' => 'text-sm py-1.5 **:data-flux-badge-icon:me-2',
        default => 'text-sm py-1 **:data-flux-badge-icon:me-1.5',
        'sm' => 'text-xs py-1 **:data-flux-badge-icon:size-3 **:data-flux-badge-icon:me-1',
    })
    ->add(match ($variant) {
        'pill' => 'rounded-full px-3',
        default => 'rounded-md px-2',
    })
    /**
     * We can't compile classes for each color because of variants color to color and Tailwind's JIT compiler.
     * We instead need to write out each one by hand. Sorry...
     */
    ->add($variant === 'solid' ? match ($color) {
        default => 'text-white bg-zinc-600 [&:is(button)]:hover:bg-zinc-700',
        'red' => 'text-white bg-red-500 [&:is(button)]:hover:bg-red-600',
        'orange' => 'text-white bg-orange-500 [&:is(button)]:hover:bg-orange-600',
        'amber' => 'text-white bg-amber-500 [&:is(button)]:hover:bg-amber-600',
        'yellow' => 'text-white bg-yellow-500 [&:is(button)]:hover:bg-yellow-600',
        'lime' => 'text-white bg-lime-500 [&:is(button)]:hover:bg-lime-600',
        'green' => 'text-white bg-green-500 [&:is(button)]:hover:bg-green-600',
        'emerald' => 'text-white bg-emerald-500 [&:is(button)]:hover:bg-emerald-600',
        'teal' => 'text-white bg-teal-500 [&:is(button)]:hover:bg-teal-600',
        'cyan' => 'text-white bg-cyan-500 [&:is(button)]:hover:bg-cyan-600',
        'sky' => 'text-white bg-sky-500 [&:is(button)]:hover:bg-sky-600',
        'blue' => 'text-white bg-blue-500 [&:is(button)]:hover:bg-blue-600',
        'indigo' => 'text-white bg-indigo-500 [&:is(button)]:hover:bg-indigo-600',
        'violet' => 'text-white bg-violet-500 [&:is(button)]:hover:bg-violet-600',
        'purple' => 'text-white bg-purple-500 [&:is(button)]:hover:bg-purple-600',
        'fuchsia' => 'text-white bg-fuchsia-500 [&:is(button)]:hover:bg-fuchsia-600',
        'pink' => 'text-white bg-pink-500 [&:is(button)]:hover:bg-pink-600',
        'rose' => 'text-white bg-rose-500 [&:is(button)]:hover:bg-rose-600',
    } :  match ($color) {
        default => 'text-zinc-700 [&_button]:text-zinc-700! bg-zinc-400/15 [&:is(button)]:hover:bg-zinc-400/25',
        'red' => 'text-red-700 [&_button]:text-red-700! bg-red-400/20 [&:is(button)]:hover:bg-red-400/30',
        'orange' => 'text-orange-700 [&_button]:text-orange-700! bg-orange-400/20 [&:is(button)]:hover:bg-orange-400/30',
        'amber' => 'text-amber-700 [&_button]:text-amber-700! bg-amber-400/25 [&:is(button)]:hover:bg-amber-400/40',
        'yellow' => 'text-yellow-800 [&_button]:text-yellow-800! bg-yellow-400/25 [&:is(button)]:hover:bg-yellow-400/40',
        'lime' => 'text-lime-800 [&_button]:text-lime-800! bg-lime-400/25 [&:is(button)]:hover:bg-lime-400/35',
        'green' => 'text-green-800 [&_button]:text-green-800! bg-green-400/20 [&:is(button)]:hover:bg-green-400/30',
        'emerald' => 'text-emerald-800 [&_button]:text-emerald-800! bg-emerald-400/20 [&:is(button)]:hover:bg-emerald-400/30',
        'teal' => 'text-teal-800 [&_button]:text-teal-800! bg-teal-400/20 [&:is(button)]:hover:bg-teal-400/30',
        'cyan' => 'text-cyan-800 [&_button]:text-cyan-800! bg-cyan-400/20 [&:is(button)]:hover:bg-cyan-400/30',
        'sky' => 'text-sky-800 [&_button]:text-sky-800! bg-sky-400/20 [&:is(button)]:hover:bg-sky-400/30',
        'blue' => 'text-blue-800 [&_button]:text-blue-800! bg-blue-400/20 [&:is(button)]:hover:bg-blue-400/30',
        'indigo' => 'text-indigo-800 [&_button]:text-indigo-800! bg-indigo-400/20 [&:is(button)]:hover:bg-indigo-400/30',
        'violet' => 'text-violet-800 [&_button]:text-violet-800! bg-violet-400/20 [&:is(button)]:hover:bg-violet-400/30',
        'purple' => 'text-purple-800 [&_button]:text-purple-800! bg-purple-400/20 [&:is(button)]:hover:bg-purple-400/30',
        'fuchsia' => 'text-fuchsia-800 [&_button]:text-fuchsia-800! bg-fuchsia-400/20 [&:is(button)]:hover:bg-fuchsia-400/30',
        'pink' => 'text-pink-800 [&_button]:text-pink-800! bg-pink-400/20 [&:is(button)]:hover:bg-pink-400/30',
        'rose' => 'text-rose-800 [&_button]:text-rose-800! bg-rose-400/20 [&:is(button)]:hover:bg-rose-400/30',
    });
@endphp

<flux:button-or-div :attributes="$attributes->class($classes)" data-flux-badge>
    <?php if (is_string($icon) && $icon !== ''): ?>
        <flux:icon :$icon :variant="$iconVariant" :class="$iconClasses" data-flux-badge-icon />
    <?php else: ?>
        {{ $icon }}
    <?php endif; ?>

    {{ $slot }}

    <?php if ($iconTrailing): ?>
        <div class="ps-1 flex items-center" data-flux-badge-icon:trailing>
            <?php if (is_string($iconTrailing)): ?>
                <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$iconClasses" />
            <?php else: ?>
                {{ $iconTrailing }}
            <?php endif; ?>
        </div>
    <?php endif; ?>
</flux:button-or-div>
