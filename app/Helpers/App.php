<?php

namespace App\Helpers;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;

class App
{
    public static function hasUsers(): bool
    {
        try {
            return User::exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function hasCategories(): bool
    {
        try {
            return Category::exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function hasTags(): bool
    {
        try {
            return Tag::exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
