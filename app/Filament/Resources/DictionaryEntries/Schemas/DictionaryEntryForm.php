<?php

namespace App\Filament\Resources\DictionaryEntries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DictionaryEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('simplified')
                    ->required()
                    ->disabled(),
                TextInput::make('traditional'),
                TextInput::make('pinyin')
                    ->required(),
                TextInput::make('pinyin_numbered'),
                Textarea::make('meaning_id')
                    ->label('Meaning (Indonesian)')
                    ->columnSpanFull(),
                Textarea::make('meaning_en')
                    ->label('Meaning (English)')
                    ->columnSpanFull(),
                Select::make('hsk_level')
                    ->options([
                        1 => 'HSK 1',
                        2 => 'HSK 2',
                        3 => 'HSK 3',
                        4 => 'HSK 4',
                        5 => 'HSK 5',
                        6 => 'HSK 6',
                    ]),
                TextInput::make('word_type')
                    ->maxLength(50),
                TextInput::make('frequency_rank')
                    ->numeric(),
                Textarea::make('notes_id')
                    ->label('Notes (Indonesian)')
                    ->columnSpanFull(),
                TextInput::make('hokkien_cognate')
                    ->maxLength(100),
                FileUpload::make('audio_url')
                    ->label('Audio')
                    ->disk('do')
                    ->directory('audio/words')
                    ->visibility('public')
                    ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4'])
                    ->maxSize(5120)
                    ->columnSpanFull(),
            ]);
    }
}
