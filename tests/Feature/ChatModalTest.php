<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Livewire\ChatModal;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Services\OllamaService;
use App\Services\OpenClawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ChatModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_only_relevant_documentation_context(): void
    {
        $category = Category::factory()->create([
            'name' => 'Infrastructure',
            'visibility' => Visibility::Public,
        ]);

        Post::factory()->for($category)->create([
            'title' => 'Database connection',
            'content' => 'Set DB_HOST to postgres and DB_PORT to 5432.',
            'visibility' => Visibility::Public,
        ]);

        Post::factory()->for($category)->create([
            'title' => 'Queue workers',
            'content' => 'Use php artisan queue:listen to process jobs.',
            'visibility' => Visibility::Public,
        ]);

        $messages = null;

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('isAvailable')->andReturn(true);
        $ollama->shouldReceive('hasModel')->andReturn(true);
        $ollama->shouldReceive('getModel')->andReturn('test-model');
        $ollama->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $payload) use (&$messages): bool {
                $messages = $payload;

                return true;
            })
            ->andReturn('Use `postgres` as the database host.');

        $this->app->instance(OllamaService::class, $ollama);

        Livewire::test(ChatModal::class)
            ->call('prompt', 'What is the database host?')
            ->assertSet('messages.1.key', 'assistant');

        $context = collect($messages)
            ->pluck('content')
            ->first(fn (string $content) => str_starts_with($content, 'RELEVANT DOCUMENTATION:')) ?? '';

        $this->assertStringContainsString('Database connection', $context);
        $this->assertStringContainsString('DB_HOST to postgres', $context);
        $this->assertStringNotContainsString('Queue workers', $context);
    }

    public function test_it_does_not_include_private_posts_in_context(): void
    {
        $privateCategory = Category::factory()->create([
            'name' => 'Private',
            'visibility' => Visibility::Private,
        ]);

        Post::factory()->for($privateCategory)->create([
            'title' => 'Internal API key',
            'content' => 'The internal API key is secret-key-123.',
            'visibility' => Visibility::Private,
        ]);

        $messages = null;

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('isAvailable')->andReturn(true);
        $ollama->shouldReceive('hasModel')->andReturn(true);
        $ollama->shouldReceive('getModel')->andReturn('test-model');
        $ollama->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $payload) use (&$messages): bool {
                $messages = $payload;

                return true;
            })
            ->andReturn('No relevant documentation found.');

        $this->app->instance(OllamaService::class, $ollama);

        Livewire::test(ChatModal::class)
            ->call('prompt', 'What is the internal API key?');

        $this->assertCount(2, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertSame('user', $messages[1]['role']);
        $this->assertStringNotContainsString('secret-key-123', json_encode($messages));
    }

    public function test_it_skips_the_context_message_when_nothing_matches(): void
    {
        $category = Category::factory()->create([
            'visibility' => Visibility::Public,
        ]);

        Post::factory()->for($category)->create([
            'title' => 'Queue workers',
            'content' => 'Use php artisan queue:listen to process jobs.',
            'visibility' => Visibility::Public,
        ]);

        $messages = null;

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldReceive('isAvailable')->andReturn(true);
        $ollama->shouldReceive('hasModel')->andReturn(true);
        $ollama->shouldReceive('getModel')->andReturn('test-model');
        $ollama->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $payload) use (&$messages): bool {
                $messages = $payload;

                return true;
            })
            ->andReturn('No relevant documentation found.');

        $this->app->instance(OllamaService::class, $ollama);

        Livewire::test(ChatModal::class)
            ->call('prompt', 'Where can I find the billing webhook secret?');

        $this->assertCount(2, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertSame('user', $messages[1]['role']);
    }

    public function test_it_uses_openclaw_when_selected_in_settings(): void
    {
        $category = Category::factory()->create([
            'name' => 'Infrastructure',
            'visibility' => Visibility::Public,
        ]);

        Post::factory()->for($category)->create([
            'title' => 'Database connection',
            'content' => 'Set DB_HOST to postgres and DB_PORT to 5432.',
            'visibility' => Visibility::Public,
        ]);

        Setting::singleton()->set('ai.provider', 'openclaw');

        $openclaw = Mockery::mock(OpenClawService::class);
        $openclaw->shouldReceive('isAvailable')->andReturn(true);
        $openclaw->shouldReceive('hasModel')->andReturn(true);
        $openclaw->shouldReceive('getModel')->andReturn('openclaw/default');
        $openclaw->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $payload): bool {
                $context = collect($payload)
                    ->pluck('content')
                    ->first(fn (string $content) => str_starts_with($content, 'RELEVANT DOCUMENTATION:')) ?? '';

                return str_contains($context, 'Database connection')
                    && str_contains($context, 'DB_HOST to postgres');
            })
            ->andReturn('Use `postgres` as the database host.');

        $this->app->instance(OpenClawService::class, $openclaw);

        $ollama = Mockery::mock(OllamaService::class);
        $ollama->shouldNotReceive('chat');
        $this->app->instance(OllamaService::class, $ollama);

        Livewire::test(ChatModal::class)
            ->call('prompt', 'What is the database host?')
            ->assertSet('messages.1.key', 'assistant')
            ->assertSet('messages.1.value', 'Use `postgres` as the database host.');
    }
}
