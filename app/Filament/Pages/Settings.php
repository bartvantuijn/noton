<?php

namespace App\Filament\Pages;

use App\Helpers\App;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Settings extends Page
{
    public Setting $setting;

    public ?array $data = [];

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Save')
                ->label(__('Save'))
                ->submit('save')
                ->action('save')
                ->formId('form'),
        ];
    }

    public function getTitle(): string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Settings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function mount(): void
    {
        $this->setting = Setting::singleton();

        $this->form->fill([
            'ai' => [
                'provider' => $this->setting->get('ai.provider', config('services.ai.provider', 'ollama')),
                'ollama' => [
                    'base_url' => $this->setting->get('ai.ollama.base_url', config('services.ollama.base_url')),
                    'model' => $this->setting->get('ai.ollama.model', config('services.ollama.model')),
                    'timeout' => $this->setting->get('ai.ollama.timeout', config('services.ollama.timeout')),
                    'pull_timeout' => $this->setting->get('ai.ollama.pull_timeout', config('services.ollama.pull_timeout')),
                    'keep_alive' => $this->setting->get('ai.ollama.keep_alive', config('services.ollama.keep_alive')),
                    'bearer_token' => $this->setting->get('ai.ollama.bearer_token', config('services.ollama.bearer_token')),
                ],
                'openclaw' => [
                    'base_url' => $this->setting->get('ai.openclaw.base_url', config('services.openclaw.base_url')),
                    'model' => $this->setting->get('ai.openclaw.model', config('services.openclaw.model')),
                    'timeout' => $this->setting->get('ai.openclaw.timeout', config('services.openclaw.timeout')),
                    'bearer_token' => $this->setting->get('ai.openclaw.bearer_token', config('services.openclaw.bearer_token')),
                ],
            ],
            'appearance' => $this->setting->get('appearance'),
            'notice' => $this->setting->get('notice'),
            'categories' => $this->getNavigationCategoryData(),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getHeaderActions()),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->setting)
            ->statePath('data')
            ->components($this->getFormComponents());
    }

    protected function getFormComponents(): array
    {
        return [
            Section::make(__('AI'))
                ->collapsible()
                ->schema([
                    Select::make('ai.provider')
                        ->label(__('Provider'))
                        ->options([
                            'ollama' => __('Ollama'),
                            'openclaw' => __('OpenClaw'),
                        ])
                        ->required()
                        ->native(false)
                        ->columnSpanFull(),

                    Section::make(__('Ollama'))
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('ai.ollama.base_url')
                                ->label(__('Base URL')),
                            TextInput::make('ai.ollama.model')
                                ->label(__('Model')),
                            TextInput::make('ai.ollama.timeout')
                                ->label(__('Timeout'))
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('ai.ollama.pull_timeout')
                                ->label(__('Pull timeout'))
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('ai.ollama.keep_alive')
                                ->label(__('Keep alive')),
                            TextInput::make('ai.ollama.bearer_token')
                                ->label(__('Bearer token'))
                                ->password()
                                ->revealable(),
                        ])->columns(2),

                    Section::make(__('OpenClaw'))
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('ai.openclaw.base_url')
                                ->label(__('Base URL')),
                            TextInput::make('ai.openclaw.model')
                                ->label(__('Model')),
                            TextInput::make('ai.openclaw.timeout')
                                ->label(__('Timeout'))
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('ai.openclaw.bearer_token')
                                ->label(__('Bearer token'))
                                ->password()
                                ->revealable(),
                        ])->columns(2),
                ])->columns(1),

            Section::make(__('Appearance'))
                ->collapsible()
                ->schema([
                    ColorPicker::make('appearance.color')
                        ->label(__('Color'))
                        ->columnSpanFull(),

                    SpatieMediaLibraryFileUpload::make('appearance.logo')
                        ->label(__('Logo'))
                        ->collection('logo')
                        ->imagePreviewHeight(200)
                        ->downloadable(),

                    SpatieMediaLibraryFileUpload::make('appearance.favicon')
                        ->label(__('Favicon'))
                        ->collection('favicon')
                        ->imagePreviewHeight(200)
                        ->downloadable(),
                ])->columns(3),

            Section::make(__('Notice'))
                ->collapsible()
                ->schema([
                    Toggle::make('notice.enabled')
                        ->label(__('Enabled'))
                        ->columnSpanFull(),
                    TextInput::make('notice.title')
                        ->label(__('Title')),
                    Select::make('notice.style')
                        ->label(__('Style'))
                        ->options([
                            'info' => __('Info'),
                            'success' => __('Success'),
                            'warning' => __('Warning'),
                            'danger' => __('Danger'),
                        ])
                        ->default('info')
                        ->native(false),
                    Textarea::make('notice.message')
                        ->label(__('Message'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make(__('Navigation'))
                ->collapsible()
                ->visible(fn () => App::hasCategories())
                ->schema([
                    $this->getNavigationRepeater('categories', 'name')
                        ->schema($this->getCategoryNavigationSchema()),
                ]),
        ];
    }

    public function save(): void
    {
        $this->setting = Setting::singleton();

        try {
            DB::transaction(function (): void {
                $state = $this->form->getState();

                $ai = $state['ai'] ?? [];
                $appearance = $state['appearance'] ?? [];
                $notice = $state['notice'] ?? [];
                $categories = $state['categories'] ?? [];

                $this->setting->set('ai', $ai);
                $this->setting->set('appearance', $appearance);
                $this->setting->set('notice', $notice);

                $this->saveNavigationCategoryOrder($categories);
            });

            Notification::make()
                ->title(__('Settings saved successfully.'))
                ->success()
                ->send();

            redirect(request()?->header('Referer'));
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();
        }
    }

    protected function getNavigationCategoryData(): array
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->get();

        $children = Category::query()
            ->orderBy('sort')
            ->get(['id', 'name', 'parent_id'])
            ->groupBy('parent_id');

        $posts = Post::query()
            ->orderBy('sort')
            ->get(['id', 'title', 'category_id'])
            ->groupBy('category_id');

        return $this->mapNavigationCategories($categories, $children, $posts);
    }

    protected function getCategoryNavigationSchema(): array
    {
        return [
            $this->getNavigationRepeater('children', 'name')
                ->visible(fn (?array $state): bool => filled($state))
                ->schema(fn () => $this->getCategoryNavigationSchema()),
            $this->getNavigationRepeater('posts', 'title', 'settings-navigation-repeater settings-navigation-repeater--posts'),
        ];
    }

    protected function getNavigationRepeater(string $name, string $label, string $class = 'settings-navigation-repeater'): Repeater
    {
        return Repeater::make($name)
            ->hiddenLabel()
            ->addable(false)
            ->deletable(false)
            ->extraAttributes(['class' => $class])
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state[$label] ?? null)
            ->collapseAllAction(fn (Action $action) => $action->hidden())
            ->expandAllAction(fn (Action $action) => $action->hidden());
    }

    protected function mapNavigationCategories(Collection $categories, Collection $children, Collection $posts): array
    {
        return $categories
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'children' => $this->mapNavigationCategories($children->get($category->id, collect()), $children, $posts),
                'posts' => $posts->get($category->id, collect())
                    ->map(fn (Post $post) => [
                        'id' => $post->id,
                        'title' => $post->title,
                    ])
                    ->all(),
            ])
            ->all();
    }

    protected function saveNavigationCategoryOrder(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $categoryIndex => $categoryData) {
            $category = Category::find($categoryData['id']);

            if (! $category) {
                continue;
            }

            $category->parent_id = $parentId;
            $category->sort = $categoryIndex;
            $category->save();

            foreach ($categoryData['posts'] ?? [] as $postIndex => $postData) {
                $post = Post::find($postData['id']);

                if (! $post) {
                    continue;
                }

                $post->sort = $postIndex;
                $post->save();
            }

            $this->saveNavigationCategoryOrder($categoryData['children'] ?? [], $category->id);
        }
    }
}
