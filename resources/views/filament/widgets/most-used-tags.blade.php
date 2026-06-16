<x-filament-widgets::widget>
    <x-filament::section class="!bg-transparent !ring-0">
        <div class="flex items-center justify-center gap-4">
            @foreach ($tags as $tag)
                <x-filament::badge>
                    {{ $tag->name }}
                </x-filament::badge>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
