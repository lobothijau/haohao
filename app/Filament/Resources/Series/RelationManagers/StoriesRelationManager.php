<?php

namespace App\Filament\Resources\Series\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'stories';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title_zh')
            ->columns([
                TextColumn::make('series_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('title_zh')
                    ->label('Title (Chinese)')
                    ->limit(40),
                TextColumn::make('title_id')
                    ->label('Title (Indonesian)')
                    ->limit(40),
                TextColumn::make('hsk_level')
                    ->label('HSK')
                    ->badge(),
                IconColumn::make('is_published')
                    ->boolean(),
            ])
            ->defaultSort('series_order');
    }
}
