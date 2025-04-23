<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center text-center">
            <div class="flex-1">
                <button type="button" wire:click="$refresh" x-init="setInterval(() => {$wire.$refresh()}, 10000)">
                    {!! $quote !!}
                </button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
