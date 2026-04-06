@php
    use App\Filament\Resources\Categories\CategoryResource;
    use App\Filament\Resources\Posts\PostResource;

    $hidden = request()->routeIs([
        'filament.admin.auth.login',
        'filament.admin.auth.register',
        'filament.admin.auth.password-reset.*',
    ]);
@endphp


@unless ($hidden)
    <div
        x-data="{ open: false }"
        x-on:keydown.escape.window="open = false"
        class="fixed right-5 bottom-5 text-end md:hidden"
    >
        <div
            x-cloak
            x-show="open"
            x-transition.origin.bottom.right
            x-on:click.outside="open = false"
            class="flex flex-col items-end gap-4 mb-4"
        >
            <x-filament::button tag="a" href="{{ CategoryResource::getUrl('create') }}" size="lg" color="gray" icon="heroicon-o-folder">
                {{ __('Create category') }}
            </x-filament::button>
            <x-filament::button tag="a" href="{{ PostResource::getUrl('create') }}" size="lg" color="gray" icon="heroicon-o-document-text">
                {{ __('Create post') }}
            </x-filament::button>
            <x-filament::button size="lg" color="primary" icon="heroicon-o-cpu-chip" x-on:click="$dispatch('open-chat-modal'); open = false">
                {{ __('Chat with Noton') }}
            </x-filament::button>
        </div>
        <x-filament::button size="lg" color="primary" icon="heroicon-o-plus" class="rounded-full shadow-lg" aria-label="{{ __('Quick actions') }}" x-on:click="open = ! open" />
    </div>
@endunless
