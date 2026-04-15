<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Pages\ViewPost;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Category;
use App\Models\Post;
use BackedEnum;
use Closure;
use Filament\Infolists\Components\SpatieTagsEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Resources\ResourceConfiguration;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, $configuration = null): string
    {
        // View routes use slugs; other routes use primary keys.
        $record = $parameters['record'] ?? null;

        if ($name === 'view' && $record instanceof Post) {
            $parameters['category'] ??= $record->category()->withoutGlobalScopes()->first();
        }

        if (($parameters['category'] ?? null) instanceof Category) {
            $parameters['category'] = $parameters['category']->getSlugPath();
        }

        if ($record instanceof Post) {
            $parameters['record'] = $name === 'view' ? $record->getSlug() : $record->getKey();
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(fn (Post $record) => $record->title)
                    ->afterHeader(fn (Post $record): View => view('filament.components.badge', ['value' => $record->visibility]))
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
            'view' => ViewPost::route('/view/{category}/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function registerRoutes(Panel $panel, ?Closure $registerPageRoutes = null, ?ResourceConfiguration $configuration = null): void
    {
        $registerPageRoutes ??= function () use ($panel, $configuration): void {
            foreach (static::getPages() as $name => $page) {
                $route = $page->registerRoute($panel);

                if ($name === 'view') {
                    $route?->where('category', '.*')->where('record', '[^/]+');
                }

                if ($configuration) {
                    $route?->middleware("resource-configuration:{$configuration->getKey()}");
                }

                $route?->name($name);
            }
        };

        parent::registerRoutes($panel, $registerPageRoutes, $configuration);
    }

    public static function resolveRecordRouteBinding(int | string $key, ?Closure $modifyQuery = null): ?Model
    {
        $query = static::getRecordRouteBindingEloquentQuery();

        if ($modifyQuery) {
            $query = $modifyQuery($query) ?? $query;
        }

        if (! request()->routeIs('filament.admin.resources.posts.view')) {
            return (clone $query)->find($key);
        }

        $category = Category::findBySlugPath((string) request()->route('category'), CategoryResource::getRecordRouteBindingEloquentQuery());

        return $category
            ? (clone $query)
                ->whereBelongsTo($category, 'category')
                ->where(fn (Builder $query) => $query->where('slug', $key)->orWhereNull('slug'))
                ->get()
                ->first(fn (Post $post) => $post->getSlug() === $key)
            : null;
    }
}
