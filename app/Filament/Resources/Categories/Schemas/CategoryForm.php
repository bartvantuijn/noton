<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\Visibility;
use App\Models\Category;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
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
                ->required(),
            Select::make('parent_id')
                ->label(__('Parent'))
                ->relationship(name: 'parent', titleAttribute: 'name', modifyQueryUsing: fn ($query) => $query->orderBy('sort'))
                ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getSelectLabel())
                ->searchable()
                ->preload(),
            ToggleButtons::make('visibility')
                ->label(__('Visibility'))
                ->required()
                ->default(Visibility::Public)
                ->options(Visibility::class)
                ->grouped(),
            MarkdownEditor::make('content')
                ->columnSpanFull(),
        ];
    }
}
