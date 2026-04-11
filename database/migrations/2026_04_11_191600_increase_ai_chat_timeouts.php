<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateStoredTimeouts(60, 600);
    }

    public function down(): void
    {
        $this->updateStoredTimeouts(600, 60);
    }

    protected function updateStoredTimeouts(int $from, int $to): void
    {
        $setting = Setting::singleton();

        foreach (['ai.ollama.timeout', 'ai.openclaw.timeout'] as $key) {
            $value = $setting->get($key);

            if ($value === null || (int) $value !== $from) {
                continue;
            }

            $setting->set($key, $to);
        }
    }
};
