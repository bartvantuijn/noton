<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
}
