<?php

namespace App\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ChatModal extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public array $messages = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('prompt')
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
            'value' => __('AI integration coming soon...'),
        ];

        $this->form->fill();
        $this->dispatch('scroll-chat-modal');
    }

    public function render(): View
    {
        return view('livewire.chat-modal');
    }
}
