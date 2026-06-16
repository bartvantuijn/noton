<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Pin;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record::withoutTimestamps(fn () => $this->record->increment('views'));
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
                        'pinnable_id' => $this->record->id,
                    ]);
                })
                ->visible(fn (): bool => Auth::check()),
            EditAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [PostResource::getUrl() => PostResource::getBreadcrumb()];

        foreach ($this->record->category->getAncestors()->push($this->record->category) as $category) {
            $breadcrumbs[CategoryResource::getUrl('view', ['record' => $category])] = $category->name;
        }

        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->title;
    }

    public function getTitle(): string
    {
        return $this->record->title;
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
