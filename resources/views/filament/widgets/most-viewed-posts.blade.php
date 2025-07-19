@php
    use App\Filament\Resources\PostResource;
    use App\Models\Post;
@endphp

<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-eye" icon-color="primary">

        <x-slot name="heading">{{ __('Most viewed posts') }}</x-slot>

        @if(Gate::allows('create', Post::class))
            <x-slot name="headerEnd">
                <x-filament::button :href="PostResource::getUrl('create')" tag="a">
                    {{ __('Create post')  }}
                </x-filament::button>
            </x-slot>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($posts as $post)
                <x-filament::section>
                    <x-slot name="heading">{{ $post->title }}</x-slot>
                    <x-slot name="headerEnd">
                        <x-filament::badge icon="heroicon-o-eye" color="gray">
                            {{ $post->views }}
                        </x-filament::badge>
                    </x-slot>

                    @if($post->tags->count())
                        <div class="flex gap-4 mb-4">
                            @foreach($post->tags as $tag)
                                <x-filament::badge>
                                    {{ $tag->name }}
                                </x-filament::badge>
                            @endforeach
                        </div>
                    @endif

                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $post->summary()->toHtml() }}</p>

                    <x-filament::button :href="PostResource::getUrl('view', ['record' => $post])" tag="a" size="sm">
                        {{ __('View') }}
                    </x-filament::button>
                </x-filament::section>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
