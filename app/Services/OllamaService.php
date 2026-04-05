<?php

namespace App\Services;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class OllamaService
{
    protected string $baseUrl;

    protected string $model;

    protected int $timeout;

    protected int $pullTimeout;

    protected string | int $keepAlive;

    protected ?string $bearerToken;

    protected Client $http;

    public function __construct()
    {
        $settings = $this->settings();

        $this->baseUrl = $settings['base_url'];
        $this->model = $settings['model'];
        $this->timeout = $settings['timeout'];
        $this->pullTimeout = $settings['pull_timeout'];
        $this->keepAlive = $settings['keep_alive'];
        $this->bearerToken = $settings['bearer_token'];

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (! empty($this->bearerToken)) {
            $headers['Authorization'] = 'Bearer ' . $this->bearerToken;
        }

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => $headers,
        ]);
    }

    protected function settings(): array
    {
        $settings = Setting::singleton()->get('ai.ollama', []);

        return [
            'base_url' => (string) Arr::get($settings, 'base_url', config('services.ollama.base_url')),
            'model' => (string) Arr::get($settings, 'model', config('services.ollama.model')),
            'timeout' => (int) Arr::get($settings, 'timeout', config('services.ollama.timeout')),
            'pull_timeout' => (int) Arr::get($settings, 'pull_timeout', config('services.ollama.pull_timeout')),
            'keep_alive' => Arr::get($settings, 'keep_alive', config('services.ollama.keep_alive')),
            'bearer_token' => Arr::get($settings, 'bearer_token', config('services.ollama.bearer_token')),
        ];
    }

    public function isAvailable(): bool
    {
        try {
            return $this->http->get('/api/tags')->getStatusCode() === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function hasModel(?string $model = null): bool
    {
        $model ??= $this->model;

        try {
            $response = json_decode($this->http->get('/api/tags')->getBody()->getContents(), true);

            foreach ($response['models'] as $m) {
                if ($m['name'] === $model) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            //
        }

        return false;
    }

    public function pull(?string $model = null): bool
    {
        $model ??= $this->model;

        try {
            $this->http->post('/api/pull', [
                'json' => [
                    'name' => $model,
                    'stream' => false,
                ],
                'timeout' => $this->pullTimeout,
            ]);

            return $this->hasModel($model);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function chat(array $messages): string
    {
        if (! $this->isAvailable()) {
            return __('Ollama is not available.');
        }

        if (! $this->hasModel() && ! $this->pull()) {
            return __(':model could not be pulled.', ['model' => $this->model]);
        }

        try {
            $response = json_decode($this->http->post('/api/chat', [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                    'keep_alive' => $this->keepAlive,
                ],
            ])->getBody()->getContents(), true);

            return $response['message']['content'] ?? 'No content.';
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
