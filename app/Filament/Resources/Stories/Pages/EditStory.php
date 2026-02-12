<?php

namespace App\Filament\Resources\Stories\Pages;

use App\Filament\Resources\Stories\StoryResource;
use App\Services\StoryProcessingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStory extends EditRecord
{
    protected static string $resource = StoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('processStory')
                ->label('Process Story')
                ->icon('heroicon-o-cog-6-tooth')
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
