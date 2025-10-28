<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    #[Computed]
    public function paginator()
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(items: range(1, 50), total: 100, perPage: 10, currentPage: 1);
    }

    #[Computed]
    public function stats()
    {
        return [
            [
                'title' => 'Total revenue',
                'value' => '$38,393.12',
                'trend' => '16.2%',
                'trendUp' => true
            ],
            [
                'title' => 'Total transactions',
                'value' => '428',
                'trend' => '12.4%',
                'trendUp' => false
            ],
            [
                'title' => 'Total customers',
                'value' => '376',
                'trend' => '12.6%',
                'trendUp' => true
            ],
            [
                'title' => 'Average order value',
                'value' => '$87.12',
                'trend' => '13.7%',
                'trendUp' => true
            ]
        ];
    }
}; ?>

<x-layouts.content heading="Heading" subheading="subheading">


    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2">
                <flux:select size="sm" class="">
                    <option>Last 7 days</option>
                    <option>Last 14 days</option>
                    <option selected>Last 30 days</option>
                    <option>Last 60 days</option>
                    <option>Last 90 days</option>
                </flux:select>
                <flux:subheading class="max-md:hidden whitespace-nowrap">compared to</flux:subheading>
                <flux:select size="sm" class="max-md:hidden">
                    <option selected>Previous period</option>
                    <option>Same period last year</option>
                    <option>Last month</option>
                    <option>Last quarter</option>
                    <option>Last 6 months</option>
                    <option>Last 12 months</option>
                </flux:select>
            </div>
            <flux:separator vertical class="max-lg:hidden mx-2 my-2" />
            <div class="max-lg:hidden flex justify-start items-center gap-2">
                <flux:subheading class="whitespace-nowrap">Filter by:</flux:subheading>
                <flux:badge as="button" variant="pill" color="zinc" icon="plus" size="lg">Amount</flux:badge>
                <flux:badge as="button" variant="pill" color="zinc" icon="plus" size="lg" class="max-md:hidden">Status
                </flux:badge>
                <flux:badge as="button" variant="pill" color="zinc" icon="plus" size="lg">More filters...</flux:badge>
            </div>
        </div>
    </div>

    <div class="flex gap-4 mb-6">
        @foreach ($this->stats as $stat)
            <div
                class="relative flex-1 rounded px-6 py-4 bg-white shadow {{ $loop->iteration > 1 ? 'max-md:hidden' : '' }}  {{ $loop->iteration > 3 ? 'max-lg:hidden' : '' }}">
                <flux:subheading>{{ $stat['title'] }}</flux:subheading>
                <flux:heading size="xl" class="mb-2">{{ $stat['value'] }}</flux:heading>
                <div
                    class="flex items-center gap-1 font-medium text-sm @if ($stat['trendUp']) text-green-600 dark:text-green-400 @else text-red-500 dark:text-red-400 @endif">
                    <flux:icon :icon="$stat['trendUp'] ? 'arrow-trending-up' : 'arrow-trending-down'" variant="micro" />
                    {{ $stat['trend'] }}
                </div>
                <div class="absolute top-0 right-0 pr-2 pt-2">
                    <flux:button icon="ellipsis-horizontal" variant="subtle" size="sm" />
                </div>
            </div>
        @endforeach
    </div>


</x-layouts.content>