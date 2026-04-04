<?php

namespace App\Livewire;

use App\Models\Post;
use App\Services\OllamaService;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
    }

    protected function systemPrompt(): string
    {
        return __(
            'You are Noton\'s documentation assistant. Always answer in clear English. ' .
            'Use only the provided documentation as your source. ' .
            'Markers like POST_START, POST_END or metadata (e.g. title:, category:, content:) are only for your understanding and must never appear in your answers. ' .
            'When the user asks for a specific value (IP, URL, path, command, key), search the provided documentation and quote it exactly. ' .
            'Give short, clear answers in Markdown. Use fenced code blocks with the correct language (e.g. ```bash) and add blank lines before and after. ' .
            'Do not invent documents or values that are not in the provided documentation. If nothing relevant is found, say so explicitly and suggest where the user could look next.'
        );
    }

    protected function relevantPosts(string $query, int $limit = 5): Collection
    {
        // Parse search terms.
        $terms = collect(preg_split('/[^[:alnum:]]+/u', mb_strtolower($query)))
            ->filter(fn (string $term) => mb_strlen($term) > 1)
            ->reject(fn (string $term) => in_array($term, [
                'a', 'an', 'and', 'are', 'can', 'for', 'how', 'in', 'is', 'of', 'on', 'the', 'this', 'to', 'use', 'what', 'with',
            ], true))
            ->unique()
            ->values();

        if ($terms->isEmpty()) {
            return Post::query()->with('category')->limit($limit)->get();
        }

        // Score matching posts.
        return Post::query()
            ->select(['id', 'category_id', 'title', 'content'])
            ->with('category')
            ->get()
            ->map(function (Post $post) use ($terms) {
                $haystack = mb_strtolower($post->title . ' ' . $post->content);

                $score = $terms->reduce(function (int $carry, string $term) use ($haystack) {
                    return $carry + substr_count($haystack, $term);
                }, 0);

                return ['post' => $post, 'score' => $score];
            })
            ->filter(fn (array $item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $item) => $item['post'])
            ->values();
    }

    protected function buildContext(string $query): string
    {
        $posts = $this->relevantPosts($query);

        if ($posts->isEmpty()) {
            return '';
        }

        // Format the context blocks.
        return $posts->map(function (Post $post) {
            return implode("\n", [
                'POST_START',
                'title: ' . $post->title,
                'category: ' . ($post->category?->name ?? 'none'),
                'content:',
                Str::limit((string) $post->content, 2500),
                'POST_END',
            ]);
        })->implode("\n");
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

        // Build the context.
        $context = $this->buildContext($prompt);

        // Keep the recent conversation.
        $history = collect($this->messages)
            ->reject(fn ($message) => $message['key'] === 'system')
            ->take(-10);

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
        ];

        // Map the chat history.
        foreach ($history as $message) {
            $messages[] = [
                'role' => $message['key'] === 'assistant' ? 'assistant' : 'user',
                'content' => $message['value'],
            ];
        }

        // Inject the context.
        if ($context !== '') {
            array_splice($messages, -1, 0, [[
                'role' => 'system',
                'content' => "RELEVANT DOCUMENTATION:\n" . $context,
            ]]);
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
