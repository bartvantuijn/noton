<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Post;
use App\Services\OllamaService;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ChatModal extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    protected OllamaService $ollama;

    public ?array $data = [];

    public array $messages = [];

    public function boot(OllamaService $ollama): void
    {
        $this->ollama = $ollama;
    }

    public function mount(): void
    {
        $this->form->fill();

        if (empty($this->messages)) {
            $this->messages[] = [
                'key' => 'system',
                'value' => $this->systemPrompt(),
            ];
            $this->messages[] = [
                'key' => 'system',
                'value' => $this->documentationIndex(),
            ];
        }
    }

    protected function systemPrompt(): string
    {
        return __(
            'You are Noton\'s documentation assistant. Always answer in clear English. ' .
            'Use only the DOCUMENTATION INDEX (categories, posts) as your source. ' .
            'Markers like POST_START, POST_END, END_INDEX or metadata (e.g. title:, category:, content:) are only for your understanding and must never appear in your answers. ' .
            'When the user asks for a specific value (IP, URL, path, command, key), search the index and quote it exactly. ' .
            'Give short, clear answers in Markdown. Use fenced code blocks with the correct language (e.g. ```bash) and add blank lines before and after. ' .
            'Do not invent documents or values that are not in the index. If nothing relevant is found, say so explicitly and suggest where the user could look next.'
        );
    }

    protected function documentationIndex(): string
    {
        return Cache::remember('documentation_index', now()->addMinutes(5), function () {
            $categories = Category::query()
                ->select('name')
                ->get()
                ->map(fn ($category) => "- {$category->name}")
                ->implode("\n");

            $posts = Post::query()
                ->select(['title', 'content'])
                ->with('category')
                ->get()
                ->map(function ($post) {
                    return implode("\n", [
                        'POST_START',
                        'title: ' . $post->title,
                        'category: ' . ($post->category->name ?? 'none'),
                        'content: ',
                        $post->content,
                        'POST_END',
                    ]);
                })
                ->implode("\n");

            return "DOCUMENTATION INDEX\n" .
                "CATEGORIES:\n{$categories}\n" .
                "POSTS:\n{$posts}\n" .
                'END INDEX';
        });
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
        $prompt = trim((string) ($state['prompt'] ?? ''));

        if ($prompt === '') {
            return;
        }

        $this->messages[] = [
            'key' => 'user',
            'value' => $prompt,
        ];

        $messages = [];
        foreach ($this->messages as $message) {
            $role = match ($message['key']) {
                'assistant' => 'assistant',
                'system' => 'system',
                default => 'user',
            };

            $messages[] = [
                'role' => $role,
                'content' => $message['value'],
            ];
        }

        $reply = $this->ollama->chat($messages);

        $this->messages[] = [
            'key' => 'assistant',
            'value' => $reply,
        ];

        $this->form->fill();
        $this->dispatch('scroll-chat-modal');
    }

    public function render(): View
    {
        return view('livewire.chat-modal');
    }
}
