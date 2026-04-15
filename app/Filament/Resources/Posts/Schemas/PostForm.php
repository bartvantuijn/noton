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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

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
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
            TextInput::make('slug')
                ->label(__('Slug'))
                ->required()
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('category_id', $get('category_id')))
                ->afterStateHydrated(fn (Get $get, Set $set, ?string $state) => $set('slug', $state ?: Str::slug($get('title')))),
            Select::make('category_id')
                ->label(__('Category'))
                ->required()
                ->default(request('category_id'))
                ->options(fn (): array => Category::getSelectOptions())
                ->searchable()
                ->preload()
                ->createOptionUsing(function (array $data): int {
                    $category = new Category($data);
                    $category->save();

                    return $category->getKey();
                })
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
