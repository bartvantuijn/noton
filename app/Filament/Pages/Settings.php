<?php

namespace App\Filament\Pages;

use App\Helpers\App;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    public Setting $setting;

    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.settings';

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
            'appearance' => $this->setting->get('appearance'),
            'categories' => Category::with('posts')->orderBy('sort')->get()
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'posts' => $category->posts()->orderBy('sort')->get()
                        ->map(fn ($post) => [
                            'id' => $post->id,
                            'title' => $post->title,
                        ]),
                ])
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->model($this->setting)
            ->statePath('data')
            ->schema($this->getFormSchema());
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('Save')
                ->label(__('Save'))
                ->submit('save'),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make(__('Appearance'))
                ->collapsible()
                ->schema([
                    Forms\Components\ColorPicker::make('appearance.color')
                        ->label(__('Color'))
                        ->columnSpanFull(),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('appearance.logo')
                        ->label(__('Logo'))
                        ->collection('logo')
                        ->imagePreviewHeight(200)
                        ->downloadable(),

                    Forms\Components\SpatieMediaLibraryFileUpload::make('appearance.favicon')
                        ->label(__('Favicon'))
                        ->collection('favicon')
                        ->imagePreviewHeight(200)
                        ->downloadable(),
                ])->columns(3),

            Forms\Components\Section::make(__('Categories'))
                ->collapsible()
                ->visible(fn () => App::hasCategories())
                ->schema([
                    Forms\Components\Repeater::make('categories')
                        ->hiddenLabel()
                        ->addable(false)
                        ->deletable(false)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->hiddenLabel()
                                ->disabled(),
                            Forms\Components\Repeater::make('posts')
                                ->label(__('Posts'))
                                ->addable(false)
                                ->deletable(false)
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->hiddenLabel()
                                        ->disabled(),
                                ])
                        ])
                ])
        ];
    }

    public function save(): void
    {
        $this->setting = Setting::singleton();

        $state = $this->form->getState();

        $appearance = $state['appearance'] ?? [];
        $categories = $state['categories'] ?? [];

        $this->setting->set('appearance', $appearance);

        foreach ($categories as $categoryIndex => $categoryData) {
            $category = Category::find($categoryData['id']);

            if ($category) {
                $category->sort = $categoryIndex;
                $category->save();

                foreach ($categoryData['posts'] as $postIndex => $postData) {
                    $post = Post::find($postData['id']);

                    if ($post) {
                        $post->sort = $postIndex;
                        $post->save();
                    }
                }
            }
        }

        Notification::make()
            ->title(__('Settings saved successfully.'))
            ->success()
            ->send();

        redirect(request()?->header('Referer'));
    }
}
