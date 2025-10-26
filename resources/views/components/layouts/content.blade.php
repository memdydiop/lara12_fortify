@props([
    'heading' => '',
    'subheading' => '',
    'actions' => '',
])

<div class="flex items-start relative">

    <div class="flex-1 self-stretch">
        
        <div class="leading-none px-4 py-1">
            <flux:heading size="lg" level="4">{{ $heading ?? '' }}</flux:heading>
            <flux:text class="text-xs">{{ $subheading ?? '' }}</flux:text>
        </div>

        <div class="p-4 space-y-4">
            {{ $slot }}
        </div>
        
    </div>

</div>