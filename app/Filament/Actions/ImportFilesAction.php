<?php

namespace App\Filament\Actions;

use App\Models\Category;
use App\Services\FileImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;

class ImportFilesAction
{
    public static function make(?Category $category = null): Action
    {
        return Action::make('import')
            ->label(__('Import files'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (): bool => auth()->check())
            ->authorize(fn (): bool => auth()->check())
            ->form([
                Select::make('category_id')
                    ->label(__('Category'))
                    ->required()
                    ->default($category?->id)
                    ->options(fn (): array => Category::getSelectOptions())
                    ->searchable()
                    ->preload(),
                FileUpload::make('files')
                    ->label(__('Files'))
                    ->multiple()
                    ->storeFiles(false)
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'text/markdown', 'text/x-markdown', 'text/plain', 'text/html'])
                    ->rules(['extensions:zip,md'])
                    ->helperText(__('.zip or .md files only.'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $importer = new FileImportService;
                $categoryId = $data['category_id'];

                foreach ($data['files'] as $file) {
                    if (! $file instanceof UploadedFile || ! $file->getRealPath()) {
                        continue;
                    }

                    $path = $file->getRealPath();
                    $extension = strtolower($file->getClientOriginalExtension());

                    if ($extension === 'zip') {
                        $importer->fromZip($path, $categoryId);

                        continue;
                    }

                    if ($extension === 'md') {
                        $importer->fromFiles([$path], $categoryId, $file->getClientOriginalName());
                    }
                }

                Notification::make()
                    ->title(__('Import complete'))
                    ->body(__(':count post(s) imported.', ['count' => $importer->created]))
                    ->success()
                    ->send();

                redirect(request()?->header('Referer'));
            });
    }
}
