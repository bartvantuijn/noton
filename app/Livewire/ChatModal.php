<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\Setting;
use App\Services\OllamaService;
use App\Services\OpenClawService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class ChatModal extends Component
{
    protected OllamaService $ollama;

    protected OpenClawService $openclaw;

    public array $messages = [];

    public function boot(OllamaService $ollama, OpenClawService $openclaw): void
    {
        $this->ollama = $ollama;
        $this->openclaw = $openclaw;
    }

    protected function provider(): string
    {
        return Setting::singleton()->get('ai.provider', config('services.ai.provider', 'ollama'));
    }

    protected function service(): OllamaService | OpenClawService
    {
        return $this->provider() === 'openclaw' ? $this->openclaw : $this->ollama;
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
                'category: ' . $post->category->name,
                'content:',
                Str::limit((string) $post->content, 2500),
                'POST_END',
            ]);
        })->implode("\n");
    }

    public function prompt(string $prompt): void
    {
        $prompt = trim($prompt);

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

        $reply = $this->service()->chat($messages);

        $this->messages[] = [
            'key' => 'assistant',
            'value' => $reply,
        ];

        $this->dispatch('scroll-chat-modal');
    }

    public function render(): View
    {
        $provider = $this->provider();
        $service = $this->service();

        if (! $service->isAvailable()) {
            $status = $provider === 'openclaw' ? __('OpenClaw is not available.') : __('Ollama is not available.');
        } elseif (! $service->hasModel()) {
            $status = $provider === 'openclaw' ? __(':model is not available.', ['model' => $service->getModel()]) : __(':model needs to be pulled, this may take a while.', ['model' => $service->getModel()]);
        } else {
            $status = $service->getModel();
        }

        $loading = $provider === 'ollama' && ! $this->ollama->hasModel() ? __('Pulling...') : __('Thinking...');

        return view('livewire.chat-modal', [
            'provider' => $provider === 'openclaw' ? __('OpenClaw') : __('Ollama'),
            'status' => $status,
            'loading' => $loading,
        ]);
    }
}
