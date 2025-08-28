<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Infolists\Components\SpatieTagsEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Phiki\CommonMark\PhikiExtension;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title';

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(fn (Post $record) => $record->title)
                    ->schema([
                        SpatieTagsEntry::make('tags')
                            ->hiddenLabel()
                            ->visible(fn (Post $post) => $post->tags()->exists()),
                        TextEntry::make('content')
                            ->hiddenLabel()
                            ->markdown()
                            ->formatStateUsing(function ($state) {
                                return Str::markdown($state, extensions: [new PhikiExtension([
                                    'light' => 'github-light-default',
                                    'dark' => 'github-dark-default',
                                ])]);
                            })
                            ->extraAttributes(['id' => 'content']),
                        TextEntry::make('views')
                            ->hiddenLabel()
                            ->badge()
                            ->color('gray')
                            ->icon(Heroicon::OutlinedEye),
                    ])->columnSpanFull(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
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
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
