<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page
{
    use InteractsWithForms;

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
        $this->form->fill([
            'data' => [
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
            ]
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('data.categories')
                    ->label(__('Categories'))
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
            ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('Save')
                ->label(__('Save'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $categories = $this->form->getState()['data']['categories'] ?? [];

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
