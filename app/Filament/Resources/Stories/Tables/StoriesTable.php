<?php

namespace App\Filament\Resources\Stories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_zh')
                    ->label('Title (Chinese)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title_id')
                    ->label('Title (Indonesian)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hsk_level')
                    ->label('HSK')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sentence_count')
                    ->label('Sentences')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('word_count')
                    ->label('Words')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_published')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_premium')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('content_source')
                    ->badge(),
                TextColumn::make('creator.name')
                    ->label('Author')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
