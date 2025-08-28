<?php

namespace App\Livewire;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Inspiring;
use Livewire\Component;

class ChatModal extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];
    public array $messages = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('prompt')
                    ->hiddenLabel()
                    ->required()
                    ->placeholder(__('Message Noton')),
            ])
            ->statePath('data')
            ->extraAttributes(['class' => 'w-full']);
    }

    public function prompt(): void
    {
        $state = $this->form->getState();

        $this->messages[] = [
            'key' => 'user',
            'value' => $state['prompt'],
        ];

        $this->messages[] = [
            'key' => 'assistant',
            'value' => __('AI integration is coming soon. In the meantime, here\'s an inspiring quote!<br><br>:quote', [
                'quote' => Inspiring::quote(),
            ]),
        ];

        $this->form->fill();
        $this->dispatch('scroll-chat-modal');
    }

    public function render(): View
    {
        return view('livewire.chat-modal');
    }
}
