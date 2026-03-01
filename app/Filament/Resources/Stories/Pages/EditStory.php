<?php

namespace App\Filament\Resources\Stories\Pages;

use App\Filament\Resources\Stories\StoryResource;
use App\Services\AiStoryParser;
use App\Services\StoryProcessingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;

class EditStory extends EditRecord
{
    protected static string $resource = StoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiProcessStory')
                ->label('AI Process Story')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->steps([
                    Step::make('Input')
                        ->description('Paste raw Chinese text')
                        ->schema([
                            Textarea::make('raw_chinese')
                                ->label('Chinese Text')
                                ->required()
                                ->rows(10)
                                ->helperText('Paste the full Chinese text. AI will split into sentences, generate pinyin, and translate.'),
                        ])
                        ->afterValidation(function (Get $get, Set $set) {
                            $rawChinese = $get('raw_chinese');
                            $hskLevel = $this->record->hsk_level ?? 3;

                            set_time_limit(120);

                            try {
                                $parser = app(AiStoryParser::class);
                                $parsed = $parser->parse($rawChinese, $hskLevel);
                                $set('parsed_sentences', $parsed);
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('AI parsing failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();

                                throw new Halt;
                            }
                        }),
                    Step::make('Review')
                        ->description('Review and edit AI output')
                        ->schema([
                            Repeater::make('parsed_sentences')
                                ->label('Sentences')
                                ->schema([
                                    Textarea::make('text_zh')
                                        ->label('Chinese')
                                        ->rows(2)
                                        ->required(),
                                    TextInput::make('text_pinyin')
                                        ->label('Pinyin')
                                        ->required(),
                                    Textarea::make('translation_id')
                                        ->label('Indonesian')
                                        ->rows(2)
                                        ->required(),
                                    Textarea::make('translation_en')
                                        ->label('English')
                                        ->rows(2),
                                ])
                                ->columns(2)
                                ->addable(false)
                                ->reorderable(false),
                        ]),
                ])
                ->action(function (array $data) {
                    $sentences = $data['parsed_sentences'] ?? [];

                    if (empty($sentences)) {
                        Notification::make()
                            ->title('No sentences to process')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $service = app(StoryProcessingService::class);
                        $stats = $service->processFromParsed($this->record, $sentences);

                        Notification::make()
                            ->title('Story processed successfully')
                            ->body(sprintf(
                                '%d sentences, %d words (%d unique), difficulty: %.1f',
                                $stats['sentence_count'],
                                $stats['word_count'],
                                $stats['unique_word_count'],
                                $stats['difficulty_score'],
                            ))
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'sentence_count',
                            'word_count',
                            'unique_word_count',
                            'difficulty_score',
                            'estimated_minutes',
                        ]);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Processing failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('processStory')
                ->label('Manual Process')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->form([
                    Textarea::make('raw_chinese')
                        ->label('Chinese Text')
                        ->required()
                        ->rows(8)
                        ->helperText('Full Chinese text. Sentences will be split by punctuation (。！？；)'),
                    Textarea::make('translations_id')
                        ->label('Translations (Indonesian)')
                        ->required()
                        ->rows(8)
                        ->helperText('One translation per line, matching sentence count'),
                    Textarea::make('translations_en')
                        ->label('Translations (English)')
                        ->rows(8)
                        ->helperText('Optional. One translation per line, matching sentence count'),
                ])
                ->action(function (array $data) {
                    $translationsId = array_values(array_filter(
                        explode("\n", $data['translations_id']),
                        fn ($line) => trim($line) !== ''
                    ));
                    $translationsId = array_map('trim', $translationsId);

                    $translationsEn = [];
                    if (! empty($data['translations_en'])) {
                        $translationsEn = array_values(array_filter(
                            explode("\n", $data['translations_en']),
                            fn ($line) => trim($line) !== ''
                        ));
                        $translationsEn = array_map('trim', $translationsEn);
                    }

                    try {
                        $service = app(StoryProcessingService::class);
                        $stats = $service->process(
                            $this->record,
                            $data['raw_chinese'],
                            $translationsId,
                            $translationsEn,
                        );

                        Notification::make()
                            ->title('Story processed successfully')
                            ->body(sprintf(
                                '%d sentences, %d words (%d unique), difficulty: %.1f',
                                $stats['sentence_count'],
                                $stats['word_count'],
                                $stats['unique_word_count'],
                                $stats['difficulty_score'],
                            ))
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'sentence_count',
                            'word_count',
                            'unique_word_count',
                            'difficulty_score',
                            'estimated_minutes',
                        ]);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()
                            ->title('Processing failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
