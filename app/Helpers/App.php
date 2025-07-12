<?php

namespace App\Helpers;

use App\Models\Category;
use App\Models\User;

class App
{
    public static function hasUsers(): bool
    {
        try {
            return User::count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function hasCategories(): bool
    {
        try {
            return Category::count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
