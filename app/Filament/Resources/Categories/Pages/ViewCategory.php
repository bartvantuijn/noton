<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Actions\ImportFilesAction;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Pin;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->increment('views');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pin')
                ->label(fn (): string => $this->isPinned() ? __('Unpin') : __('Pin'))
                ->icon(fn (): Heroicon => $this->isPinned() ? Heroicon::Star : Heroicon::OutlinedStar)
                ->color(fn (): string => $this->isPinned() ? 'warning' : 'gray')
                ->action(function (): void {
                    $pin = Pin::query()
                        ->whereBelongsTo(Auth::user())
                        ->where('pinnable_type', $this->record->getMorphClass())
                        ->where('pinnable_id', $this->record->id);

                    $pin->exists() ? $pin->delete() : Pin::create([
                        'user_id' => Auth::id(),
                        'pinnable_type' => $this->record->getMorphClass(),
                        'pinnable_id' => $this->record->id
                    ]);
                })
                ->visible(fn (): bool => Auth::check()),
            ImportFilesAction::make($this->record),
            Action::make('create')
                ->label(__('Create post'))
                ->url(fn (): string => PostResource::getUrl('create', ['category_id' => $this->record->id]))
                ->visible(fn (): bool => Gate::allows('create', Post::class)),
            EditAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [CategoryResource::getUrl() => CategoryResource::getBreadcrumb()];

        foreach ($this->record->getAncestors() as $ancestor) {
            $breadcrumbs[CategoryResource::getUrl('view', ['record' => $ancestor])] = $ancestor->name;
        }

        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->name;
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function isPinned(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        return Pin::query()
            ->whereBelongsTo(Auth::user())
            ->where('pinnable_type', $this->record->getMorphClass())
            ->where('pinnable_id', $this->record->id)
            ->exists();
    }
}
