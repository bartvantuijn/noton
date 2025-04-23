<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    public function getBreadcrumb(): string
    {
        return $this->record->title;
    }

    public function getTitle(): string
    {
        return $this->record->title;
    }
}
