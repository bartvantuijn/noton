<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];

    public static function singleton(): static
    {
        try {
            return static::firstOrCreate(['id' => 1]);
        } catch (\Throwable $e) {
            return new static(['data' => []]);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data ?? [], $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $data = $this->data ?? [];
        Arr::set($data, $key, $value);

        $this->update(['data' => $data]);
    }
}
