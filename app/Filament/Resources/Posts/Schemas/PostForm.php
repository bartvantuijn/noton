<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\Visibility;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Category;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getFormComponents());
    }

    public static function getFormComponents(): array
    {
        return [
            TextInput::make('title')
                ->label(__('Title'))
                ->required(),
            Select::make('category_id')
                ->label(__('Category'))
                ->required()
                ->default(request('category_id'))
                ->relationship(name: 'category', titleAttribute: 'name')
                ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getSelectLabel())
                ->searchable()
                ->preload()
                ->createOptionForm(CategoryForm::getFormComponents()),
            ToggleButtons::make('visibility')
                ->label(__('Visibility'))
                ->required()
                ->default(Visibility::Public)
                ->options(Visibility::class)
                ->grouped(),
            SpatieTagsInput::make('tags')
                ->reorderable()
                ->columnSpan('full'),
            MarkdownEditor::make('content')
                ->required()
                ->columnSpan('full'),
        ];
    }
}
