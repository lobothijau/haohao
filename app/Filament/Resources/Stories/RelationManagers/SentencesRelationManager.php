<?php

namespace App\Filament\Resources\Stories\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SentencesRelationManager extends RelationManager
{
    protected static string $relationship = 'sentences';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position')
            ->columns([
                TextColumn::make('position')
                    ->sortable(),
                TextColumn::make('text_zh')
                    ->label('Chinese')
                    ->limit(40),
                TextColumn::make('text_pinyin')
                    ->label('Pinyin')
                    ->limit(40),
                TextColumn::make('translation_id')
                    ->label('Translation (ID)')
                    ->limit(40),
                TextColumn::make('words_count')
                    ->counts('words')
                    ->label('Words'),
            ])
            ->defaultSort('position');
    }
}
