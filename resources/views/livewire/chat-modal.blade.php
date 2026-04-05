@php
    use Phiki\CommonMark\PhikiExtension;
@endphp

<div
    x-data="{
        prompt: '',
        pendingPrompt: '',
        async send() {
            const value = this.prompt.trim();

            if (! value) {
                return;
            }

            this.pendingPrompt = value;
            this.prompt = '';

            $nextTick(() => window.Livewire.dispatch('scroll-chat-modal'));

            await $wire.prompt(value);
            this.pendingPrompt = '';
        },
    }"
>
    <x-filament::modal slide-over>
        <x-slot name="trigger">
            <x-filament::icon-button icon="heroicon-o-cpu-chip"></x-filament::icon-button>
        </x-slot>

        <x-slot name="heading">{{ __('Chat') }}</x-slot>
        <x-slot name="description">{{ __('Chat with Noton') }}</x-slot>

        <div id="chat" class="flex flex-col gap-4 max-h-[80vh] overflow-y-auto">
            <div class="flex items-center gap-4 text-xs text-gray-500">
                <x-filament::icon icon="heroicon-o-language" class="h-4 w-4" />
                <span>{{ $provider }} · {{ $status }}</span>
            </div>

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

            <div x-cloak x-show="pendingPrompt" class="bg-primary-500 self-end p-2 rounded-lg">
                <p x-text="pendingPrompt"></p>
            </div>

            <div wire:loading.flex wire:target="prompt" class="items-center gap-4 text-xs text-gray-500">
                <x-filament::loading-indicator class="h-4 w-4" />
                <span>{{ $loading }}</span>
            </div>
        </div>

        <x-slot name="footer">
            <form x-on:submit.prevent="send" class="flex items-center gap-4">
                <x-filament::input.wrapper class="w-full">
                    <x-filament::input x-model="prompt" type="text" autocomplete="off" :placeholder="__('Message Noton')" />
                </x-filament::input.wrapper>

                <x-filament::icon-button icon="heroicon-o-arrow-up" color="gray" type="submit"/>
            </form>
        </x-slot>
    </x-filament::modal>
</div>
