<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            CategoryResource::getUrl() => CategoryResource::getBreadcrumb(),
        ];

        foreach ($this->record->getAncestors() as $category) {
            $breadcrumbs[CategoryResource::getUrl('view', ['record' => $category])] = $category->name;
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
