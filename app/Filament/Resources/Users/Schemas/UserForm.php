<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::getFormComponents());
    }

    public static function getFormComponents(): array
    {
        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->required(),
            TextInput::make('email')
                ->label(__('Email'))
                ->required()
                ->email(),
            Select::make('role')
                ->label(__('Role'))
                ->required()
                ->searchable()
                ->preload()
                ->options([
                    'admin' => __('Admin'),
                    'user' => __('User'),
                ])
                ->columnSpan('full'),
            TextInput::make('password')
                ->label(__('Password'))
                ->required(fn (string $context) => $context === 'create')
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->confirmed()
                ->dehydrated(fn ($state) => filled($state)),
            TextInput::make('password_confirmation')
                ->label(__('Confirm password'))
                ->required(fn (string $context) => $context === 'create')
                ->password()
                ->revealable()
                ->dehydrated(false),
        ];
    }
}
