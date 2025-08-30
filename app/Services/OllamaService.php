<?php

namespace App\Services;

use GuzzleHttp\Client;

class OllamaService
{
    public function __construct()
    {
        $this->baseUrl = config('services.ollama.base_url');
        $this->model = config('services.ollama.model');
        $this->timeout = config('services.ollama.timeout');
        $this->pullTimeout = config('services.ollama.pull_timeout');

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
        ]);
    }

    public function isAvailable(): bool
    {
        try {
            return $this->http->get('/api/tags')->getStatusCode() === 200;
        } catch (\Throwable $e) {
            return false;
        }
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
                    'keep_alive' => '1h',
                ],
            ])->getBody()->getContents(), true);

            return $response['message']['content'] ?? 'No content.';
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
