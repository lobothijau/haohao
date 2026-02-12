<?php

namespace App\Filament\Resources\DictionaryEntries\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DictionaryEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('simplified')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pinyin')
                    ->searchable(),
                TextColumn::make('meaning_en')
                    ->label('Meaning (English)')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('meaning_id')
                    ->label('Meaning (Indonesian)')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hsk_level')
                    ->label('HSK')
                    ->badge()
                    ->sortable(),
                TextColumn::make('word_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('frequency_rank')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hsk_level')
                    ->label('HSK Level')
                    ->options([
                        1 => 'HSK 1',
                        2 => 'HSK 2',
                        3 => 'HSK 3',
                        4 => 'HSK 4',
                        5 => 'HSK 5',
                        6 => 'HSK 6',
                    ]),
                SelectFilter::make('word_type')
                    ->options([
                        'noun' => 'Noun',
                        'verb' => 'Verb',
                        'adjective' => 'Adjective',
                        'adverb' => 'Adverb',
                    ]),
                TernaryFilter::make('has_indonesian')
                    ->label('Has Indonesian Translation')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('meaning_id'),
                        false: fn ($query) => $query->whereNull('meaning_id'),
                    ),
            ])
            ->defaultPaginationPageOption(25)
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
