<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        if ($this->record->isLastAdmin() && $this->data['role'] !== 'admin') {
            Notification::make()
                ->title(__('There must be at least one admin.'))
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
