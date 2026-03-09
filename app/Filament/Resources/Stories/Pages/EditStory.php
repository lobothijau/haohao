<?php

namespace App\Filament\Resources\Stories\Pages;

use App\Filament\Resources\Stories\StoryResource;
use App\Jobs\ProcessStoryManualJob;
use App\Jobs\ProcessStoryWithAiJob;
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
            Action::make('aiProcessStory')
                ->label('AI Process Story')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->form([
                    Textarea::make('raw_chinese')
                        ->label('Chinese Text')
                        ->required()
                        ->rows(10)
                        ->helperText('Paste the full Chinese text. AI will split into sentences, generate pinyin, and translate.'),
                ])
                ->action(function (array $data) {
                    $hskLevel = $this->record->hsk_level ?? 3;

                    ProcessStoryWithAiJob::dispatch(
                        $this->record,
                        $data['raw_chinese'],
                        $hskLevel,
                    );

                    Notification::make()
                        ->title('AI processing started')
                        ->body('Sentences will appear in the relation manager once processing completes.')
                        ->success()
                        ->send();
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
                        ->helperText('Full Chinese text. Sentences will be split by punctuation.'),
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

                    ProcessStoryManualJob::dispatch(
                        $this->record,
                        $data['raw_chinese'],
                        $translationsId,
                        $translationsEn,
                    );

                    Notification::make()
                        ->title('Processing started')
                        ->body('Sentences will appear in the relation manager once processing completes.')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
