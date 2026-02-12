<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_id')
                    ->label('Name (Indonesian)')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_en')
                    ->label('Name (English)')
                    ->maxLength(255),
                TextInput::make('icon')
                    ->maxLength(50),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
