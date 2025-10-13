<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\Visibility;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getFormComponents());
    }

    public static function getFormComponents(): array
    {
        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->columnSpan('full'),
            ToggleButtons::make('visibility')
                ->label(__('Visibility'))
                ->required()
                ->default(Visibility::Public)
                ->options(Visibility::class)
                ->grouped(),
        ];
    }
}
