<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Phiki\CommonMark\PhikiExtension;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getModelLabel(): string
    {
        return __('Post');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Posts');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'content'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        // Get search query
        $query = collect(request('components'))
            ->pluck('updates.search')
            ->filter()
            ->first();

        return [
            $record->summary(100, $query),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        // Get search query
        $query = collect(request('components'))
            ->pluck('updates.search')
            ->filter()
            ->first();

        return static::getUrl('view', ['record' => $record, 'query' => $query]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->heading(fn (Post $record) => $record->title)
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('edit')
                            ->label(__('Edit'))
                            ->url(fn (Post $record) => static::getUrl('edit', ['record' => $record]))
                            ->visible(fn () => auth()->check()),
                    ])
                    ->schema([
                        Infolists\Components\SpatieTagsEntry::make('tags')
                            ->hiddenLabel()
                            ->visible(fn(Post $post) => $post->tags()->exists()),
                        Infolists\Components\TextEntry::make('content')
                            ->hiddenLabel()
                            ->markdown()
                            ->formatStateUsing(function ($state) {
                                return Str::markdown($state, extensions: [new PhikiExtension([
                                    'light' => 'github-light-default',
                                    'dark' => 'github-dark-default',
                                ])]);
                            })
                            ->extraAttributes(['id' => 'content']),
                        Infolists\Components\TextEntry::make('views')
                            ->hiddenLabel()
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-o-eye'),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('Title'))
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->label(__('Category'))
                    ->required()
                    ->default(request('category_id'))
                    ->relationship(name: 'category', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm(CategoryResource::getFormSchema()),
                Forms\Components\SpatieTagsInput::make('tags')
                    ->reorderable()
                    ->columnSpan('full'),
                Forms\Components\MarkdownEditor::make('content')
                    ->required()
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->description(fn (Post $post): string => $post->summary(50)),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label(__('Tags'))
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('views')
                    ->label(__('Views'))
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-eye'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tags')
                    ->label(__('Tags'))
                    ->searchable()
                    ->preload()
                    ->relationship('tags', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
