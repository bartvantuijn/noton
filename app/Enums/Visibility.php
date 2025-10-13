<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Visibility: string implements HasColor, HasIcon, HasLabel
{
    case Public = 'public';
    case Private = 'private';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Public => __('Public'),
            self::Private => __('Private'),
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::Public => 'primary',
            self::Private => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Public => 'heroicon-o-eye',
            self::Private => 'heroicon-o-eye-slash',
        };
    }
}
