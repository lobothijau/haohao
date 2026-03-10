<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class StorySentence extends Model
{
    /** @use HasFactory<\Database\Factories\StorySentenceFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @var list<string> */
    protected $appends = ['audio_src'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'story_id',
        'position',
        'paragraph',
        'text_zh',
        'translation_id',
        'translation_en',
        'audio_url',
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
