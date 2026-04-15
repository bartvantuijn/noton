<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [PostResource::getUrl() => PostResource::getBreadcrumb()];

        foreach ($this->record->category->getAncestors()->push($this->record->category) as $category) {
            $breadcrumbs[CategoryResource::getUrl('view', ['record' => $category])] = $category->name;
        }

        $breadcrumbs[PostResource::getUrl('view', ['record' => $this->record])] = $this->record->title;
        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
