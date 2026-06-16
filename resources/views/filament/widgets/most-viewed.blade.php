@php
    use App\Filament\Resources\Posts\PostResource;
    use App\Models\Post;
@endphp

<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-eye" icon-color="primary">

        <x-slot name="heading">{{ __('Most viewed') }}</x-slot>

        @if(Gate::allows('create', Post::class))
            <x-slot name="afterHeader">
                <x-filament::button :href="PostResource::getUrl('create')" tag="a">
                    {{ __('Create post')  }}
                </x-filament::button>
            </x-slot>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($records as $record)
                @include('filament.widgets.content-card', ['record' => $record])
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
