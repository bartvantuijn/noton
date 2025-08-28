<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Resources\Categories\Schemas\CategoryForm;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
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
                ->searchable()
                ->preload()
                ->createOptionForm(CategoryForm::getFormComponents()),
            SpatieTagsInput::make('tags')
                ->reorderable()
                ->columnSpan('full'),
            MarkdownEditor::make('content')
                ->required()
                ->columnSpan('full'),
        ];
    }
}
