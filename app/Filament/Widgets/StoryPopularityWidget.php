<?php

namespace App\Filament\Widgets;

use App\Enums\ReadingStatus;
use App\Models\Story;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class StoryPopularityWidget extends TableWidget
{
    protected static ?string $heading = 'Top Stories';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Story::query()
                    ->withCount(['readingProgress as readers_count'])
                    ->withCount(['readingProgress as completed_count' => function ($query) {
                        $query->where('status', ReadingStatus::Completed);
                    }])
                    ->where('is_published', true)
                    ->orderByDesc('readers_count')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('title_id')
                    ->label('Story')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('hsk_level')
                    ->label('HSK')
                    ->badge()
                    ->sortable(),
                TextColumn::make('readers_count')
                    ->label('Readers')
                    ->sortable(),
                TextColumn::make('completed_count')
                    ->label('Completed')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
