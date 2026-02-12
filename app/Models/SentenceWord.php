<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SentenceWord extends Model
{
    /** @use HasFactory<\Database\Factories\SentenceWordFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'story_sentence_id',
        'dictionary_entry_id',
        'position',
        'surface_form',
    ];

    /**
     * @return BelongsTo<StorySentence, $this>
     */
    public function sentence(): BelongsTo
    {
        return $this->belongsTo(StorySentence::class, 'story_sentence_id');
    }

    /**
     * @return BelongsTo<DictionaryEntry, $this>
     */
    public function dictionaryEntry(): BelongsTo
    {
        return $this->belongsTo(DictionaryEntry::class);
    }
}
