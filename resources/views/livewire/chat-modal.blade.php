<x-filament::modal slide-over>
    <x-slot name="trigger">
        <x-filament::icon-button icon="heroicon-o-cpu-chip"></x-filament::icon-button>
    </x-slot>

    <x-slot name="heading">{{ __('Chat') }}</x-slot>
    <x-slot name="description">{{ __('Chat with Noton') }}</x-slot>

    <div id="chat" class="flex flex-col gap-4 max-h-[80vh] overflow-y-auto">
        @foreach ($messages as $message)
            <div class="@if($message['key'] === 'user') bg-primary-500 self-end @else bg-gray-100 dark:bg-gray-800 self-start @endif p-2 rounded-lg">
                {!! $message['value'] !!}
            </div>
        @endforeach
    </div>

    <x-slot name="footer">
        <form wire:submit="prompt" class="flex items-center gap-4">
            {{ $this->form }}

            <x-filament::icon-button icon="heroicon-o-arrow-up" color="gray" type="submit"/>
        </form>
    </x-slot>
</x-filament::modal>
