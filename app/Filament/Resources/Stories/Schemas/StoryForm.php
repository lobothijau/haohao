<?php

namespace App\Filament\Resources\Stories\Schemas;

use App\Enums\ContentSource;
use App\Services\PinyinService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Story Details')
                    ->schema([
                        TextInput::make('title_zh')
                            ->label('Title (Chinese)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set) {
                                if ($state !== null && $state !== '') {
                                    $set('title_pinyin', app(PinyinService::class)->convert($state));
                                }
                            }),
                        TextInput::make('title_pinyin')
                            ->label('Title (Pinyin)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('title_id')
                            ->label('Title (Indonesian)')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description_id')
                            ->label('Description (Indonesian)')
                            ->columnSpanFull(),
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
                        Select::make('content_source')
                            ->options(ContentSource::class)
                            ->default('manual')
                            ->required(),
                        Select::make('categories')
                            ->relationship('categories', 'name_id')
                            ->multiple()
                            ->preload(),
                    ]),
                Section::make('Series')
                    ->schema([
                        Select::make('series_id')
                            ->relationship('series', 'title_id')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('series_order')
                            ->label('Chapter Order')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                    ]),
                Section::make('Publishing')
                    ->schema([
                        Toggle::make('is_premium')
                            ->default(false),
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
                Section::make('Processing Stats')
                    ->schema([
                        Placeholder::make('sentence_count_display')
                            ->label('Sentences')
                            ->content(fn ($record) => $record?->sentence_count ?? 0),
                        Placeholder::make('word_count_display')
                            ->label('Total Words')
                            ->content(fn ($record) => $record?->word_count ?? 0),
                        Placeholder::make('unique_word_count_display')
                            ->label('Unique Words')
                            ->content(fn ($record) => $record?->unique_word_count ?? 0),
                        Placeholder::make('difficulty_score_display')
                            ->label('Difficulty Score')
                            ->content(fn ($record) => $record?->difficulty_score ?? 'N/A'),
                        Placeholder::make('estimated_minutes_display')
                            ->label('Est. Reading Time')
                            ->content(fn ($record) => ($record?->estimated_minutes ?? 0).' min'),
                    ])
                    ->visibleOn('edit')
                    ->columns(5),
            ]);
    }
}
