<?php

namespace App\Services;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class OpenClawService
{
    protected string $baseUrl;

    protected string $model;

    protected int $timeout;

    protected ?string $bearerToken;

    protected Client $http;

    public function __construct()
    {
        $settings = $this->settings();

        $this->baseUrl = $settings['base_url'];
        $this->model = $settings['model'];
        $this->timeout = $settings['timeout'];
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
        $settings = Setting::singleton()->get('ai.openclaw', []);

        return [
            'base_url' => (string) Arr::get($settings, 'base_url', config('services.openclaw.base_url')),
            'model' => (string) Arr::get($settings, 'model', config('services.openclaw.model')),
            'timeout' => (int) Arr::get($settings, 'timeout', config('services.openclaw.timeout')),
            'bearer_token' => Arr::get($settings, 'bearer_token', config('services.openclaw.bearer_token')),
        ];
    }

    public function isAvailable(): bool
    {
        try {
            return $this->http->get('/models')->getStatusCode() === 200;
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
            $response = json_decode($this->http->get('/models')->getBody()->getContents(), true);

            foreach ($response['data'] ?? [] as $item) {
                if (($item['id'] ?? null) === $model) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            //
        }

        return false;
    }

    public function chat(array $messages): string
    {
        if (! $this->isAvailable()) {
            return __('OpenClaw is not available.');
        }

        if (! $this->hasModel()) {
            return __(':model is not available.', ['model' => $this->model]);
        }

        try {
            $response = json_decode($this->http->post('/chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ],
            ])->getBody()->getContents(), true);

            return $response['choices'][0]['message']['content'] ?? 'No content.';
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
