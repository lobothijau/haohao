<?php

namespace App\Filament\Resources\DictionaryEntries;

use App\Filament\Resources\DictionaryEntries\Pages\EditDictionaryEntry;
use App\Filament\Resources\DictionaryEntries\Pages\ListDictionaryEntries;
use App\Filament\Resources\DictionaryEntries\Schemas\DictionaryEntryForm;
use App\Filament\Resources\DictionaryEntries\Tables\DictionaryEntriesTable;
use App\Models\DictionaryEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DictionaryEntryResource extends Resource
{
    protected static ?string $model = DictionaryEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function form(Schema $schema): Schema
    {
        return DictionaryEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DictionaryEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDictionaryEntries::route('/'),
            'edit' => EditDictionaryEntry::route('/{record}/edit'),
        ];
    }
}
