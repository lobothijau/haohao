# Claude Code Implementation Guide
# Nihao — Step-by-Step Build Plan

This document is designed to be fed to Claude Code as context when building each phase.

---

## Phase 1: Foundation (Week 1-2)

### Step 1.1: Project Scaffolding
```bash
# Create Laravel project
Empty laravel project has been setup with Vue starter kit

Inertia and Vue should be installed. 

# Install Tailwind if not installed
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Install Filament for admin
composer require filament/filament
php artisan filament:install --panels

# Install Socialite for Google OAuth
Social login disabled for now but if you still want to prepare it (it's fine)

# Additional packages
composer require spatie/laravel-permission     # roles (admin/user)
composer require spatie/laravel-sluggable      # auto-slugs for stories
composer require spatie/laravel-medialibrary   # file uploads
```

### Step 1.2: Database Migrations
Create migrations in this order:
1. Modify `users` table (add hsk_level, google_id, is_premium, streak fields)
2. `categories`
3. `dictionary_entries`
4. `dictionary_examples`
5. `stories`
6. `story_sentences`
7. `sentence_words`
8. `category_story` (pivot)
9. `user_vocabularies`
10. `srs_cards`
11. `srs_review_logs`
12. `reading_progress`
13. `user_preferences`
14. `subscriptions`

### Step 1.3: Models & Relationships
Key relationships to define:
- `Story` hasMany `StorySentence` (ordered by position)
- `StorySentence` hasMany `SentenceWord` (ordered by position)
- `SentenceWord` belongsTo `DictionaryEntry`
- `User` hasMany `UserVocabulary`
- `User` hasMany `SrsCard`
- `UserVocabulary` hasOne `SrsCard`
- `Story` belongsToMany `Category`
- `User` hasMany `ReadingProgress`
- `User` hasOne `UserPreference`

### Step 1.4: Auth Setup
- Configure Breeze with Inertia (already done in scaffolding)
- Add Google OAuth via Socialite
- Create `LoginController` handling both email and Google
- Add `hsk_level` selection to registration flow
- Create `UserPreference` record on registration

---

## Phase 2: Dictionary & Content System (Week 2-3)

### Step 2.1: Dictionary Import Command
```
php artisan dictionary:import {file}
```
- Parse CC-CEDICT format
- Map to `dictionary_entries` table
- Tag HSK levels from HSK word list CSV
- This is a one-time seed command

### Step 2.2: Story Admin Panel (Filament)
Create Filament resources for:
- `StoryResource` — CRUD for stories with:
  - Rich text input for Chinese content
  - Auto-fill pinyin and HSK level on save
  - Category assignment
  - Free/premium toggle
  - Publish/unpublish
- `CategoryResource` — manage categories
- `DictionaryEntryResource` — browse/edit dictionary

### Step 2.3: Story Processing Service
When a story is saved in admin:
1. Split text into sentences (by Chinese punctuation: 。！？)
2. Segment each sentence into words (jieba)
3. Look up each word in `dictionary_entries`
4. Create `story_sentences` and `sentence_words` records
5. Calculate story-level stats (word_count, hsk_level, etc.)
6. Generate pinyin for each sentence

**Implementation option for jieba in Laravel:**
```php
// Option A: Shell out to Python
$process = new Process(['python3', 'scripts/segment.py', $text]);
$process->run();
$words = json_decode($process->getOutput());

// Option B: Use a PHP port or API
// https://github.com/fukuball/jieba-php (PHP port, less accurate but simpler)
composer require fukuball/jieba-php
```

---

## Phase 3: Reader (Week 3-5) — CORE FEATURE

### Step 3.1: Reader Controller
```php
class ReaderController extends Controller
{
    public function show(Story $story)
    {
        // Check premium access
        if ($story->is_premium && !auth()->user()->is_premium) {
            return redirect()->route('premium');
        }
        
        // Load story with sentences and words
        $story->load([
            'sentences.words.dictionaryEntry',
            'categories'
        ]);
        
        // Get user's saved vocabulary IDs for highlighting
        $savedWordIds = auth()->user()
            ->vocabularies()
            ->pluck('dictionary_entry_id')
            ->toArray();
        
        // Get/create reading progress
        $progress = ReadingProgress::firstOrCreate(
            ['user_id' => auth()->id(), 'story_id' => $story->id],
            ['status' => 'in_progress', 'started_at' => now()]
        );
        
        // User preferences
        $preferences = auth()->user()->preference;
        
        return Inertia::render('Reader/Show', [
            'story' => $story,
            'savedWordIds' => $savedWordIds,
            'progress' => $progress,
            'preferences' => $preferences,
        ]);
    }
}
```

### Step 3.2: Reader Vue Page (`Pages/Reader/Show.vue`)

Key composable: `useReader.js`
```javascript
// Manages: current sentence, word selection, preferences, progress sync
export function useReader(story, preferences) {
    const showPinyin = ref(preferences.show_pinyin);
    const showTranslation = ref(preferences.show_translation);
    const selectedWord = ref(null);
    const currentSentence = ref(0);
    
    // Word click handler
    function selectWord(sentenceWord) {
        selectedWord.value = sentenceWord.dictionary_entry;
    }
    
    // Save progress (debounced, sent to server)
    const saveProgress = useDebounceFn(() => {
        router.post(`/read/${story.id}/progress`, {
            last_sentence_position: currentSentence.value
        }, { preserveState: true });
    }, 2000);
    
    return { showPinyin, showTranslation, selectedWord, selectWord, saveProgress };
}
```

### Step 3.3: Key Reader Components

**SentenceBlock.vue** — Most critical component
```
┌────────────────────────────────────────────┐
│  pinyin line (conditional)                 │
│  character line (each word is clickable)   │
│  translation line (conditional)        🔊  │
└────────────────────────────────────────────┘
```

**WordPopup.vue** — Appears on word click
- Positioned near clicked word (use floating-ui or Tippy.js)
- Shows: character, pinyin, meaning, HSK badge, example, save button
- "Already saved" state if word is in user's vocabulary

### Step 3.4: Vocabulary Saving from Reader
```php
// VocabularyController@store
public function store(Request $request)
{
    $validated = $request->validate([
        'dictionary_entry_id' => 'required|exists:dictionary_entries,id',
        'source_story_id' => 'nullable|exists:stories,id',
        'source_sentence_id' => 'nullable|exists:story_sentences,id',
    ]);
    
    // Check free tier limit (50 words)
    if (!auth()->user()->is_premium) {
        $count = auth()->user()->vocabularies()->count();
        if ($count >= 50) {
            return back()->with('error', 'premium_required');
        }
    }
    
    $vocab = auth()->user()->vocabularies()->firstOrCreate(
        ['dictionary_entry_id' => $validated['dictionary_entry_id']],
        $validated
    );
    
    // Auto-create SRS card
    if ($vocab->wasRecentlyCreated) {
        SrsCard::create([
            'user_id' => auth()->id(),
            'dictionary_entry_id' => $validated['dictionary_entry_id'],
            'user_vocabulary_id' => $vocab->id,
            'card_state' => 'new',
            'due_at' => now(),
        ]);
    }
    
    return back()->with('success', 'word_saved');
}
```

---

## Phase 4: SRS Review System (Week 5-7)

### Step 4.1: SRS Service
```php
// app/Services/SRSService.php
class SRSService
{
    const LEARNING_STEPS_MINUTES = [1, 10];
    const GRADUATING_INTERVAL_DAYS = 1;
    const EASY_INTERVAL_DAYS = 4;
    const MIN_EASE = 1.30;
    const EASY_BONUS = 1.30;
    const HARD_INTERVAL_MULTIPLIER = 1.20;
    
    public function getDueCards(User $user, int $limit = null): Collection
    {
        $preferences = $user->preference;
        
        $newLimit = $preferences->new_cards_per_day - $this->newCardsStudiedToday($user);
        $reviewLimit = $preferences->max_reviews_per_day - $this->reviewsDoneToday($user);
        
        $newCards = SrsCard::where('user_id', $user->id)
            ->where('card_state', 'new')
            ->orderBy('created_at')
            ->limit(max(0, $newLimit))
            ->get();
            
        $dueCards = SrsCard::where('user_id', $user->id)
            ->whereIn('card_state', ['learning', 'review', 'relearning'])
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->limit(max(0, $reviewLimit))
            ->get();
        
        return $this->interleaveCards($newCards, $dueCards, $preferences->card_order);
    }
    
    public function grade(SrsCard $card, int $rating): SrsCard
    {
        // Log the review
        SrsReviewLog::create([...]);
        
        // Apply SM-2 algorithm based on current state
        return match($card->card_state) {
            'new', 'learning' => $this->gradeLearningCard($card, $rating),
            'review' => $this->gradeReviewCard($card, $rating),
            'relearning' => $this->gradeRelearningCard($card, $rating),
        };
    }
    
    private function gradeReviewCard(SrsCard $card, int $rating): SrsCard
    {
        if ($rating === 1) { // Again — lapse
            $card->card_state = 'relearning';
            $card->learning_step = 0;
            $card->lapses += 1;
            $card->ease_factor = max(self::MIN_EASE, $card->ease_factor - 0.20);
            $card->due_at = now()->addMinutes(self::LEARNING_STEPS_MINUTES[0]);
        } else {
            $card->repetitions += 1;
            
            // Update ease factor (SM-2 formula)
            $card->ease_factor = max(
                self::MIN_EASE,
                $card->ease_factor + (0.1 - (5 - $rating) * (0.08 + (5 - $rating) * 0.02))
            );
            
            // Calculate new interval
            $interval = match($rating) {
                2 => $card->interval_days * self::HARD_INTERVAL_MULTIPLIER,           // Hard
                3 => $card->interval_days * $card->ease_factor,                        // Good
                4 => $card->interval_days * $card->ease_factor * self::EASY_BONUS,    // Easy
            };
            
            $card->interval_days = max(1, round($interval));
            $card->due_at = now()->addDays($card->interval_days);
        }
        
        $card->last_reviewed_at = now();
        $card->save();
        
        return $card;
    }
}
```

### Step 4.2: Review Session Controller
```php
class ReviewController extends Controller
{
    public function session(SRSService $srs)
    {
        $cards = $srs->getDueCards(auth()->user());
        
        // Load dictionary data for each card
        $cards->load('dictionaryEntry.examples');
        
        return Inertia::render('Review/Session', [
            'cards' => $cards,
            'stats' => [
                'new' => $cards->where('card_state', 'new')->count(),
                'learning' => $cards->where('card_state', 'learning')->count(),
                'review' => $cards->where('card_state', 'review')->count(),
            ]
        ]);
    }
    
    public function grade(Request $request, SRSService $srs)
    {
        $validated = $request->validate([
            'card_id' => 'required|exists:srs_cards,id',
            'rating' => 'required|integer|between:1,4',
            'time_taken_ms' => 'nullable|integer',
        ]);
        
        $card = SrsCard::where('user_id', auth()->id())
            ->findOrFail($validated['card_id']);
            
        $srs->grade($card, $validated['rating']);
        
        // Return next due time for this card (for learning cards that come back soon)
        return back()->with('graded', [
            'card_id' => $card->id,
            'next_due' => $card->due_at,
            'new_state' => $card->card_state,
        ]);
    }
}
```

### Step 4.3: Review Session Vue (`Pages/Review/Session.vue`)
- Card flip animation (CSS 3D transform)
- Keyboard shortcuts: Space = flip, 1-4 = rate
- Timer per card (for analytics)
- Progress bar showing remaining cards
- Learning cards re-queue in session when due within session time

---

## Phase 5: Dashboard & Library (Week 7-8)

### Step 5.1: Dashboard
Shows:
- Cards due today (big CTA: "Review X Kata")
- Current streak + streak calendar
- Recently read stories (resume reading)
- Suggested stories for user's level
- Weekly progress chart (words learned, reviews done)

### Step 5.2: Story Library
- Grid of story cards
- Filter by: HSK level (pills), category, free/premium
- Sort by: newest, most read, recommended
- Search by title (Chinese/pinyin/Indonesian)
- Pagination (or infinite scroll)
- Premium stories show lock icon

---

## Phase 6: Payment & Premium (Week 8-9)

### Step 6.1: Midtrans Integration
```bash
composer require midtrans/midtrans-php
```
- Create `SubscriptionController` for checkout flow
- Midtrans Snap popup for payment (no redirect needed)
- Webhook handler for payment confirmation
- Update `is_premium` and `premium_expires_at` on success

### Step 6.2: Premium Gating
```php
// Middleware: EnsurePremium
class EnsurePremium
{
    public function handle($request, Closure $next)
    {
        if (!$request->user()->is_premium) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'premium_required'], 403);
            }
            return redirect()->route('premium');
        }
        return $next($request);
    }
}

// Or use inline checks:
// Reader: $story->is_premium && !auth()->user()->is_premium
// Vocabulary: count >= 50 && !premium
```

---

## Phase 7: Content Pipeline (Week 9-10)

### Step 7.1: AI Story Generation Command
```
php artisan story:generate --level=2 --topic="daily life" --length=short
```
- Calls Claude API with HSK vocabulary constraints
- Generates Chinese story + sentence-by-sentence Indonesian translations
- Saves as draft in admin panel for human review
- Auto-processes (segment, tag, grade) on publish

### Step 7.2: TTS Audio Generation
```
php artisan audio:generate {story_id}
```
- Queued job: generates TTS for each sentence
- Uses Azure Speech Service or Google Cloud TTS (Mandarin)
- Stores audio files in S3/DO Spaces
- Updates `audio_url` on `story_sentences`

### Step 7.3: Seed Content
- Import 30-40 starter stories (HSK 1-3)
- Mix of: classic stories adapted, daily life scenarios, cultural topics
- Ensure good mix of free and premium content per level

---

## Testing Priorities

| Area | Type | Priority |
|------|------|----------|
| SRS grading algorithm | Unit tests | Critical |
| Word segmentation accuracy | Unit tests | Critical |
| Vocabulary save/SRS card creation | Feature tests | High |
| Reader progress tracking | Feature tests | High |
| Premium gating (story access, vocab limit) | Feature tests | High |
| Payment webhook handling | Feature tests | High |
| Dictionary lookup | Feature tests | Medium |
| Auth flows (email + Google) | Feature tests | Medium |

---

## Claude Code Session Prompts

When working with Claude Code, use these as starting prompts for each session:

**Session 1 — Scaffolding:**
> "Set up a Laravel 12 + Vue 3 + Inertia.js project. Install Tailwind CSS, Filament 3 for admin, and Socialite for Google OAuth. Create all database migrations based on [paste schema]. Create Eloquent models with relationships."

**Session 2 — Dictionary:**
> "Create a Laravel command to import CC-CEDICT dictionary data into the dictionary_entries table. Parse the standard CC-CEDICT format and tag HSK levels from an HSK word list CSV."

**Session 3 — Admin Panel:**
> "Build Filament admin resources for Stories, Categories, and Dictionary Entries. The Story resource should support creating stories with auto word segmentation and HSK level calculation on save."

**Session 4 — Reader:**
> "Build the interactive reader page. Create Reader/Show.vue with SentenceBlock, WordPopup components. Implement tap-to-translate, pinyin toggle, translation toggle. Create useReader composable for state management."

**Session 5 — SRS:**
> "Implement the SM-2 spaced repetition algorithm in SRSService.php. Create the review session page with card flip animation, rating buttons (Again/Hard/Good/Easy), keyboard shortcuts, and session progress tracking."

**Session 6 — Dashboard & Library:**
> "Build the user dashboard with cards-due widget, streak display, and suggested stories. Build the story library with HSK level filters, category filters, search, and pagination."
