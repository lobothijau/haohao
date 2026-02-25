<?php

namespace App\Filament\Resources\Series\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Series Details')
                    ->schema([
                        TextInput::make('title_zh')
                            ->label('Title (Chinese)')
                            ->required()
                            ->maxLength(500),
                        TextInput::make('title_pinyin')
                            ->label('Title (Pinyin)')
                            ->required()
                            ->maxLength(500),
                        TextInput::make('title_id')
                            ->label('Title (Indonesian)')
                            ->required()
                            ->maxLength(500),
                        Textarea::make('description_id')
                            ->label('Description (Indonesian)')
                            ->columnSpanFull(),
                        TextInput::make('cover_image_url')
                            ->label('Cover Image URL')
                            ->url()
                            ->maxLength(500),
                        Select::make('hsk_level')
                            ->options([
                                1 => 'HSK 1',
                                2 => 'HSK 2',
                                3 => 'HSK 3',
                                4 => 'HSK 4',
                                5 => 'HSK 5',
                                6 => 'HSK 6',
                            ])
                            ->required(),
                    ]),
                Section::make('Publishing')
                    ->schema([
                        Toggle::make('is_published')
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('published_at', now());
                                } else {
                                    $set('published_at', null);
                                }
                            }),
                        DateTimePicker::make('published_at')
                            ->visibleOn('edit'),
                        Hidden::make('created_by')
                            ->default(fn () => auth()->id()),
                    ]),
            ]);
    }
}
