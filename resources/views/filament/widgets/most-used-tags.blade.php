@php
    use App\Filament\Resources\PostResource;
@endphp

<x-filament-widgets::widget>
    <x-filament::section class="!bg-transparent !ring-0">
        <div class="flex items-center justify-center gap-4">
            @foreach ($tags as $tag)
                <a href="{{ PostResource::getUrl('index', ['tableFilters[tags][value]' => $tag->id]) }}">
                    <x-filament::badge>
                        {{ $tag->name }}
                    </x-filament::badge>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
