<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->increment('views');
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
}
