<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class DictionaryEntry extends Model
{
    /** @use HasFactory<\Database\Factories\DictionaryEntryFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['audio_src'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'simplified',
        'traditional',
        'pinyin',
        'pinyin_numbered',
        'meaning_id',
        'meaning_en',
        'hsk_level',
        'word_type',
        'frequency_rank',
        'audio_url',
        'notes_id',
        'hokkien_cognate',
        'created_by_user_id',
    ];

    /**
     * @return Attribute<string|null, never>
     */
    protected function audioSrc(): Attribute
    {
        return Attribute::get(function (): ?string {
            $value = $this->attributes['audio_url'] ?? null;

            if ($value === null) {
                return null;
            }

            if (str_starts_with($value, 'http')) {
                return $value;
            }

            return Storage::disk('do')->url($value);
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<DictionaryExample, $this>
     */
    public function examples(): HasMany
    {
        return $this->hasMany(DictionaryExample::class);
    }

    /**
     * @return HasMany<SentenceWord, $this>
     */
    public function sentenceWords(): HasMany
    {
        return $this->hasMany(SentenceWord::class);
    }

    /**
     * @return HasMany<UserVocabulary, $this>
     */
    public function userVocabularies(): HasMany
    {
        return $this->hasMany(UserVocabulary::class);
    }
}
