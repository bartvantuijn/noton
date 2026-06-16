@php
    use App\Filament\Resources\Categories\CategoryResource;
    use App\Filament\Resources\Posts\PostResource;
    use App\Models\Post;
    use Illuminate\Support\Str;

    $title = $record instanceof Post ? $record->title : $record->name;
    $subtitle = Str::limit($record->subtitle, 20);
    $summary = $record->summary();
    $url = $record instanceof Post ? PostResource::getUrl('view', ['record' => $record]) : CategoryResource::getUrl('view', ['record' => $record]);
@endphp

<x-filament::section>
    <x-slot name="heading">{{ $title }}</x-slot>
    <x-slot name="description">{{ $subtitle }}</x-slot>
    <x-slot name="afterHeader">
        <x-filament::badge icon="heroicon-o-eye" color="gray">
            {{ $record->views }}
        </x-filament::badge>
    </x-slot>

    @if($record->tags->count())
        <div class="flex gap-4 mb-4">
            @foreach($record->tags as $tag)
                <x-filament::badge>
                    {{ $tag->name }}
                </x-filament::badge>
            @endforeach
        </div>
    @endif

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $summary->toHtml() }}</p>

    <x-filament::button :href="$url" tag="a" size="sm">
        {{ __('View') }}
    </x-filament::button>
</x-filament::section>
