<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-star" icon-color="warning">
        <x-slot name="heading">{{ __('Pinned') }}</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($pins as $pin)
                @include('filament.widgets.content-card', ['record' => $pin->pinnable])
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
