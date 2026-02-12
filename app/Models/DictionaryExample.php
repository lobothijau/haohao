<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictionaryExample extends Model
{
    /** @use HasFactory<\Database\Factories\DictionaryExampleFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'dictionary_entry_id',
        'sentence_zh',
        'sentence_pinyin',
        'sentence_id',
    ];

    /**
     * @return BelongsTo<DictionaryEntry, $this>
     */
    public function dictionaryEntry(): BelongsTo
    {
        return $this->belongsTo(DictionaryEntry::class);
    }
}
