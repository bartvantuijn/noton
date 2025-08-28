<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->description(fn (Post $post): string => $post->summary(50)),
                TextColumn::make('tags.name')
                    ->label(__('Tags'))
                    ->searchable()
                    ->badge(),
                TextColumn::make('views')
                    ->label(__('Views'))
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedEye),
            ])
            ->filters([
                SelectFilter::make('tags')
                    ->label(__('Tags'))
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->relationship('tags', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
