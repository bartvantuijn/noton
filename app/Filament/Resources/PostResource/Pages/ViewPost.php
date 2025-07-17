<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\PostResource;
use App\Models\Category;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->increment('views');
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            PostResource::getUrl() => PostResource::getBreadcrumb(),
        ];

        if ($category = $this->record->category) {
            $breadcrumbs[CategoryResource::getUrl('view', ['record' => $category]) ] = $category->name;
        }

        $breadcrumbs += parent::getBreadcrumbs();

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
