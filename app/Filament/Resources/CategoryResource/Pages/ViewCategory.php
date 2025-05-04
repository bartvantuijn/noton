<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            CategoryResource::getUrl() => CategoryResource::getBreadcrumb(),
        ];

        $breadcrumbs += parent::getBreadcrumbs();

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
