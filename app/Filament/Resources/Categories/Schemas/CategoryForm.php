<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
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
        ];
    }
}
