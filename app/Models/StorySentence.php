<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorySentence extends Model
{
    /** @use HasFactory<\Database\Factories\StorySentenceFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'story_id',
        'position',
        'text_zh',
        'text_pinyin',
        'translation_id',
        'translation_en',
        'audio_url',
    ];

    /**
     * @return BelongsTo<Story, $this>
     */
    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * @return HasMany<SentenceWord, $this>
     */
    public function words(): HasMany
    {
        return $this->hasMany(SentenceWord::class)->orderBy('position');
    }
}
