<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use BackedEnum;
use Closure;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Resources\ResourceConfiguration;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Phiki\CommonMark\PhikiExtension;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Categories');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'content'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [
            __('Posts') => $record->posts->count(),
        ];

        if ($record->parent) {
            $details = [
                __('Parent') => $record->parent->name,
                ...$details,
            ];
        }

        return $details;
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, $configuration = null): string
    {
        // View routes use slugs; other routes use primary keys.
        if (($parameters['record'] ?? null) instanceof Category) {
            $parameters['record'] = $name === 'view' ? $parameters['record']->getSlugPath() : $parameters['record']->getKey();
        }

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(fn (Category $record) => $record->name)
                    ->afterHeader(fn (Category $record): View => view('filament.components.badge', ['value' => $record->visibility]))
                    ->schema([
                        TextEntry::make('parent.name')
                            ->label(__('Parent'))
                            ->visible(fn (Category $record) => filled($record->parent_id))
                            ->url(fn (Category $record) => static::getUrl('view', ['record' => $record->parent]))
                            ->icon(Heroicon::OutlinedFolder),
                        RepeatableEntry::make('children')
                            ->label(__('Subcategories'))
                            ->visible(fn (Category $record) => $record->children()->exists())
                            ->schema([
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->url(fn (Category $category) => static::getUrl('view', ['record' => $category]))
                                    ->icon(Heroicon::OutlinedFolder),
                            ]),
                        RepeatableEntry::make('posts')
                            ->label(__('Posts'))
                            ->visible(fn (Category $record) => $record->posts()->exists())
                            ->schema([
                                TextEntry::make('title')
                                    ->hiddenLabel()
                                    ->url(fn (Post $post) => PostResource::getUrl('view', ['record' => $post]))
                                    ->icon(Heroicon::OutlinedDocumentText),
                            ]),
                        TextEntry::make('content')
                            ->hiddenLabel()
                            ->markdown()
                            ->visible(fn (?string $state) => filled($state))
                            ->formatStateUsing(function ($state) {
                                return Str::markdown($state, extensions: [new PhikiExtension([
                                    'light' => 'github-light-default',
                                    'dark' => 'github-dark-default',
                                ])]);
                            })
                            ->extraAttributes(['id' => 'content']),
                    ])->columnSpanFull(),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/view/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function registerRoutes(Panel $panel, ?Closure $registerPageRoutes = null, ?ResourceConfiguration $configuration = null): void
    {
        $registerPageRoutes ??= function () use ($panel, $configuration): void {
            foreach (static::getPages() as $name => $page) {
                $route = $page->registerRoute($panel);

                if ($name === 'view') {
                    $route?->where('record', '.*');
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

        if (! request()->routeIs('filament.admin.resources.categories.view')) {
            return (clone $query)->find($key);
        }

        return Category::findBySlugPath((string) $key, $query);
    }
}
