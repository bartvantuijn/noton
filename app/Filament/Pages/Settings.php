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
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
                ]),
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

            Section::make(__('Navigation'))
                ->collapsible()
                ->visible(fn () => App::hasCategories())
                ->schema([
                    Repeater::make('categories')
                        ->hiddenLabel()
                        ->addable(false)
                        ->deletable(false)
                        ->schema([
                            TextInput::make('name')
                                ->hiddenLabel()
                                ->disabled(),
                            Repeater::make('posts')
                                ->label(__('Posts'))
                                ->addable(false)
                                ->deletable(false)
                                ->schema([
                                    TextInput::make('title')
                                        ->hiddenLabel()
                                        ->disabled(),
                                ]),
                        ]),
                ]),
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
