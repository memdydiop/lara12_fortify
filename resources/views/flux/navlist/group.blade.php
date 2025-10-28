@pure

@props([
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
    'icon' => null,
    'iconVariant' => 'outline',
    'iconDot' => null,
    'iconClasses' => null,
])

@php
    // Button should be a square if it has no text contents...
    $square ??= $slot->isEmpty();

    // Size-up icons in square/icon-only buttons...
    $iconClasses = Flux::classes($square ? 'size-5!' : 'size-4!');

    $classes = Flux::classes()
        ->add('group/disclosure rounded overflow-hidden')
        ->add('hover:bg-zinc-800/5')
        ->add('data-open:bg-zinc-800/10')
    ;
    $buttonClasses = Flux::classes()
        ->add('w-full h-8 flex items-center gap-x-3 group/disclosure-button')
        ->add($square ? 'px-2.5!' : '')
        ->add('py-0 text-start px-3')
        ->add('text-menu-item hover:bg-zinc-800/5')
        ->add('data-open:bg-zinc-800/5  data-open:border-b data-open:border-zinc-800/15')
    ;
@endphp

<?php if ($expandable && $heading): ?>
    <ui-disclosure {{ $attributes->class($classes) }} @if ($expanded === true) open @endif data-flux-navlist-group>
        <button type="button" class="{!! $buttonClasses !!}"">
            <?php if ($icon): ?>
                <div class="relative">
                    <?php if (is_string($icon) && $icon !== ''): ?>
                        <flux:icon :$icon :variant="$iconVariant" class="{!! $iconClasses !!}" />
                    <?php else: ?>
                        {{ $icon }}
                    <?php endif; ?>

                    <?php if ($iconDot): ?>
                        <div class="absolute top-[-2px] end-[-2px]">
                            <div class="size-[6px] rounded-full bg-zinc-500"></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?> 
            <span class="text-sm font-medium leading-none flex-1">{{ $heading }}</span>
            
            <div class="px-0">
                <flux:icon.chevron-down class="size-3! hidden group-data-open/disclosure-button:block" />
                <flux:icon.chevron-right class="size-3! block group-data-open/disclosure-button:hidden rtl:rotate-180" />
            </div>
        </button>

        <div class="relative hidden data-open:block space-y-[2px] ps-1.5" @if ($expanded === true) data-open @endif>
            {{ $slot }}
        </div>
    </ui-disclosure>
<?php elseif ($heading): ?>
    <div {{ $attributes->class('block space-y-[2px]') }}>
        <div class="px-3 py-2">
            <div class="text-sm text-zinc-400 font-medium leading-none">{{ $heading }}</div>
        </div>

        <div>
            {{ $slot }}
        </div>
    </div>
<?php else: ?>
    <div {{ $attributes->class('block space-y-[2px]') }}>
        {{ $slot }}
    </div>
<?php endif; ?>
