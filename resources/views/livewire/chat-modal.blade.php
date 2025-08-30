@php
    use Phiki\CommonMark\PhikiExtension;
@endphp

<div>
    <x-filament::modal slide-over>
        <x-slot name="trigger">
            <x-filament::icon-button icon="heroicon-o-cpu-chip"></x-filament::icon-button>
        </x-slot>

        <x-slot name="heading">{{ __('Chat') }}</x-slot>
        <x-slot name="description">{{ __('Chat with Noton') }}</x-slot>

        <div id="chat" class="flex flex-col gap-4 max-h-[80vh] overflow-y-auto">
            @if (! $this->ollama->isAvailable())
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <x-filament::icon icon="heroicon-o-language" class="h-4 w-4" />
                    <span>{{ __('Ollama is not available.') }}</span>
                </div>
            @elseif(! $this->ollama->hasModel())
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <x-filament::icon icon="heroicon-o-language" class="h-4 w-4" />
                    <span>{{ __(':model needs to be pulled, this may take a while.', ['model' => $this->ollama->model]) }}</span>
                </div>
            @else
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <x-filament::icon icon="heroicon-o-language" class="h-4 w-4" />
                    <span>{{ $this->ollama->model }}</span>
                </div>
            @endif

            @foreach ($messages as $message)
                @if ($message['key'] === 'system')
                    @continue
                @endif

                <div class="@if($message['key'] === 'user') bg-primary-500 self-end @else bg-gray-100 dark:bg-gray-800 self-start @endif p-2 rounded-lg">
                    {!!
                        Str::markdown($message['value'], extensions: [new PhikiExtension([
                            'light' => 'github-light-default',
                            'dark' => 'github-dark-default',
                        ])])
                    !!}
                </div>
            @endforeach

            <div wire:loading.flex wire:target="prompt" class="items-center gap-4 text-xs text-gray-500">
                <x-filament::loading-indicator class="h-4 w-4" />
                @if(! $this->ollama->hasModel())
                    <span>{{ __('Pulling...') }}</span>
                @else
                    <span>{{ __('Thinking...') }}</span>
                @endif
            </div>
        </div>

        <x-slot name="footer">
            <form wire:submit="prompt" class="flex items-center gap-4">
                {{ $this->form }}

                <x-filament::icon-button icon="heroicon-o-arrow-up" color="gray" type="submit"/>
            </form>
        </x-slot>
    </x-filament::modal>
</div>
