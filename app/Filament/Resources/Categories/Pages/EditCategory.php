<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [CategoryResource::getUrl() => CategoryResource::getBreadcrumb()];

        foreach ($this->record->getAncestors() as $ancestor) {
            $breadcrumbs[CategoryResource::getUrl('view', ['record' => $ancestor])] = $ancestor->name;
        }

        $breadcrumbs[CategoryResource::getUrl('view', ['record' => $this->record])] = $this->record->name;
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

    protected function beforeSave(): void
    {
        try {
            /** @var Category $category */
            $category = clone $this->getRecord();
            $category->fill($this->data);

            $category->validateParent();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
