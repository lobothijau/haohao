<?php

namespace App\Filament\Resources\Stories\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SentencesRelationManager extends RelationManager
{
    protected static string $relationship = 'sentences';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('text_zh')
                ->label('Chinese')
                ->content(fn ($record): string => $record->text_zh),
            Placeholder::make('text_pinyin')
                ->label('Pinyin')
                ->content(fn ($record): string => $record->text_pinyin),
            FileUpload::make('audio_url')
                ->label('Audio')
                ->disk('do')
                ->directory('audio/sentences')
                ->visibility('public')
                ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'])
                ->maxSize(10240)
                ->columnSpanFull(),
        ]);
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
                IconColumn::make('audio_url')
                    ->label('Audio')
                    ->icon(fn (?string $state): string => $state ? 'heroicon-o-speaker-wave' : 'heroicon-o-minus')
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('words_count')
                    ->counts('words')
                    ->label('Words'),
            ])
            ->defaultSort('position')
            ->actions([
                EditAction::make(),
            ]);
    }
}
