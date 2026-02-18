<?php

namespace App\Models;

use App\Enums\ContentSource;
use App\Models\Concerns\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Story extends Model
{
    /** @use HasFactory<\Database\Factories\StoryFactory> */
    use HasComments, HasFactory, HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title_zh',
        'title_pinyin',
        'title_id',
        'slug',
        'description_id',
        'hsk_level',
        'difficulty_score',
        'word_count',
        'unique_word_count',
        'sentence_count',
        'estimated_minutes',
        'thumbnail_url',
        'is_premium',
        'is_published',
        'published_at',
        'content_source',
        'created_by',
    ];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title_id')
            ->saveSlugsTo('slug');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hsk_level' => 'integer',
            'difficulty_score' => 'decimal:2',
            'is_premium' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'content_source' => ContentSource::class,
        ];
    }

    /**
     * @return HasMany<StorySentence, $this>
     */
    public function sentences(): HasMany
    {
        return $this->hasMany(StorySentence::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ReadingProgress, $this>
     */
    public function readingProgress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }
}
