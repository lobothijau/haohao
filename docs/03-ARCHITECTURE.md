# Architecture & Database Design
# MandarinID — Laravel + Vue + Inertia

---

## 1. Tech Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| **Backend** | Laravel 12 | PHP 8.3+ |
| **Frontend** | Vue 3 (Composition API) | With `<script setup>` |
| **Bridge** | Inertia.js v2 | Server-side routing, SPA-like UX |
| **CSS** | Tailwind CSS 3 | Utility-first |
| **Database** | MySQL 8 | Laravel default, good enough for MVP |
| **Cache** | Redis | Sessions, SRS queue, rate limiting |
| **Search** | Laravel Scout + Meilisearch | Story/dictionary search |
| **Admin** | Filament 3 | Admin panel for content management |
| **Auth** | Laravel Breeze (Inertia) | Email + Google Socialite |
| **Payments** | Midtrans PHP SDK | Indonesian payment gateway |
| **TTS** | Azure Cognitive Services / Google TTS / Prioritize free services and plan to prevent cost outage | Mandarin pronunciation |
| **Queue** | Laravel Queue (Redis driver) | TTS generation, AI processing |
| **Storage** | S3-compatible (DigitalOcean Spaces / AWS) | Audio files, images |
| **Hosting** | DigitalOcean / Forge / Ploi | Laravel deployment |

---

## 2. Application Architecture

```
┌─────────────────────────────────────────────────────────┐
│                        CLIENT                            │
│  ┌────────────────────────────────────────────────────┐  │
│  │                   Vue 3 + Inertia                  │  │
│  │  ┌──────────┐ ┌──────────┐ ┌───────────────────┐  │  │
│  │  │  Pages   │ │Components│ │    Composables     │  │  │
│  │  │ Dashboard│ │ Reader   │ │ useReader()        │  │  │
│  │  │ Library  │ │ WordCard │ │ useSRS()           │  │  │
│  │  │ Reader   │ │ SRSCard  │ │ useVocabulary()    │  │  │
│  │  │ Review   │ │ AudioBtn │ │ useAudio()         │  │  │
│  │  │ Vocab    │ │ PinyinTgl│ │ usePreferences()   │  │  │
│  │  │ Profile  │ │ StoryCard│ │                    │  │  │
│  │  └──────────┘ └──────────┘ └───────────────────┘  │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────┬──────────────────────────────┘
                           │ Inertia Requests
                           ▼
┌─────────────────────────────────────────────────────────┐
│                    LARAVEL BACKEND                        │
│  ┌─────────────────────────────────────────────────┐    │
│  │              Middleware Stack                     │    │
│  │  Auth │ Subscription │ Locale │ HandleInertia    │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  ┌────────────────┐  ┌────────────────┐  ┌───────────┐  │
│  │  Controllers   │  │   Services     │  │  Actions   │  │
│  │  ReaderCtrl    │  │  SRSService    │  │ SaveWord   │  │
│  │  LibraryCtrl   │  │  ReaderService │  │ GradeCard  │  │
│  │  ReviewCtrl    │  │  StoryService  │  │ GenAudio   │  │
│  │  VocabCtrl     │  │  DictionaryServ│  │ ImportStory│  │
│  │  DashboardCtrl │  │  AudioService  │  │            │  │
│  └────────────────┘  └────────────────┘  └───────────┘  │
│                                                          │
│  ┌────────────────┐  ┌────────────────┐                  │
│  │    Models       │  │   Filament     │                  │
│  │  (Eloquent)     │  │  Admin Panel   │                  │
│  └────────┬───────┘  └────────────────┘                  │
│           │                                              │
└───────────┼──────────────────────────────────────────────┘
            │
            ▼
┌────────────────────┐  ┌──────────┐  ┌──────────────┐
│     MySQL 8        │  │  Redis   │  │  S3 Storage  │
│  (Primary DB)      │  │ (Cache/  │  │ (Audio/Images│
│                    │  │  Queue)  │  │              │
└────────────────────┘  └──────────┘  └──────────────┘
```

---

## 3. Database Schema

### Entity Relationship Overview

```
users ──────────┬──── user_vocabularies ──── dictionary_entries
                │
                ├──── srs_cards
                │
                ├──── reading_progress
                │
                ├──── subscriptions
                │
                └──── user_preferences

stories ────────┬──── story_sentences ──── sentence_words ──── dictionary_entries
                │
                ├──── story_categories (pivot)
                │
                └──── story_audio_files

categories

dictionary_entries ──── dictionary_examples
```

### Table Definitions

#### `users`
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NULL,          -- null for OAuth-only users
    google_id VARCHAR(255) NULL UNIQUE,
    avatar_url VARCHAR(500) NULL,
    hsk_level TINYINT UNSIGNED DEFAULT 1,  -- self-reported: 1-6
    locale VARCHAR(5) DEFAULT 'id',        -- 'id' or 'en'
    timezone VARCHAR(50) DEFAULT 'Asia/Jakarta',
    is_premium BOOLEAN DEFAULT FALSE,
    premium_expires_at TIMESTAMP NULL,
    streak_count INT UNSIGNED DEFAULT 0,
    streak_last_date DATE NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_hsk_level (hsk_level),
    INDEX idx_premium (is_premium, premium_expires_at)
);
```

#### `stories`
```sql
CREATE TABLE stories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title_zh VARCHAR(500) NOT NULL,        -- 小猫找朋友
    title_pinyin VARCHAR(500) NOT NULL,    -- Xiǎo Māo Zhǎo Péngyou
    title_id VARCHAR(500) NOT NULL,        -- Kucing Kecil Mencari Teman
    slug VARCHAR(500) NOT NULL UNIQUE,
    description_id TEXT NULL,              -- Indonesian description/teaser
    hsk_level TINYINT UNSIGNED NOT NULL,   -- 1-6
    difficulty_score DECIMAL(3,2) NULL,    -- 1.00-6.00 precise difficulty
    word_count INT UNSIGNED DEFAULT 0,
    unique_word_count INT UNSIGNED DEFAULT 0,
    sentence_count INT UNSIGNED DEFAULT 0,
    estimated_minutes TINYINT UNSIGNED DEFAULT 5,
    thumbnail_url VARCHAR(500) NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    content_source ENUM('manual', 'ai_generated', 'adapted') DEFAULT 'manual',
    created_by BIGINT UNSIGNED NULL,       -- admin user who created it
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_hsk_published (hsk_level, is_published, is_premium),
    INDEX idx_slug (slug),
    FULLTEXT idx_search (title_zh, title_pinyin, title_id)
);
```

#### `story_sentences`
```sql
CREATE TABLE story_sentences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    story_id BIGINT UNSIGNED NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,     -- order within story
    text_zh TEXT NOT NULL,                    -- 小猫很可爱。
    text_pinyin TEXT NOT NULL,                -- Xiǎo māo hěn kě ài.
    translation_id TEXT NOT NULL,             -- Kucing kecil sangat lucu.
    translation_en TEXT NULL,                 -- The little cat is very cute. (optional)
    audio_url VARCHAR(500) NULL,             -- TTS audio file URL
    
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    INDEX idx_story_position (story_id, position)
);
```

#### `dictionary_entries`
```sql
CREATE TABLE dictionary_entries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    simplified VARCHAR(50) NOT NULL,         -- 可爱
    traditional VARCHAR(50) NULL,            -- 可愛
    pinyin VARCHAR(100) NOT NULL,            -- kě ài
    pinyin_numbered VARCHAR(100) NULL,       -- ke3 ai4 (for sorting/search)
    meaning_id TEXT NOT NULL,                -- Lucu, menggemaskan
    meaning_en TEXT NULL,                    -- cute, adorable, lovely
    hsk_level TINYINT UNSIGNED NULL,         -- 1-6, null if not in HSK
    word_type VARCHAR(50) NULL,              -- adjective, verb, noun, etc.
    frequency_rank INT UNSIGNED NULL,        -- word frequency ranking
    audio_url VARCHAR(500) NULL,             -- pronunciation audio
    notes_id TEXT NULL,                      -- Indonesian-specific notes
    hokkien_cognate VARCHAR(100) NULL,       -- related Hokkien word if any
    
    UNIQUE INDEX idx_simplified_pinyin (simplified, pinyin),
    INDEX idx_hsk (hsk_level),
    INDEX idx_frequency (frequency_rank),
    FULLTEXT idx_search (simplified, traditional, pinyin, meaning_id, meaning_en)
);
```

#### `dictionary_examples`
```sql
CREATE TABLE dictionary_examples (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dictionary_entry_id BIGINT UNSIGNED NOT NULL,
    sentence_zh VARCHAR(500) NOT NULL,
    sentence_pinyin VARCHAR(500) NOT NULL,
    sentence_id VARCHAR(500) NOT NULL,       -- Indonesian translation
    
    FOREIGN KEY (dictionary_entry_id) REFERENCES dictionary_entries(id) ON DELETE CASCADE,
    INDEX idx_entry (dictionary_entry_id)
);
```

#### `sentence_words` (Links sentences to dictionary — the word segmentation result)
```sql
CREATE TABLE sentence_words (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    story_sentence_id BIGINT UNSIGNED NOT NULL,
    dictionary_entry_id BIGINT UNSIGNED NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,     -- word order in sentence
    surface_form VARCHAR(50) NOT NULL,       -- actual text as it appears
    
    FOREIGN KEY (story_sentence_id) REFERENCES story_sentences(id) ON DELETE CASCADE,
    FOREIGN KEY (dictionary_entry_id) REFERENCES dictionary_entries(id) ON DELETE CASCADE,
    INDEX idx_sentence (story_sentence_id, position),
    INDEX idx_entry (dictionary_entry_id)
);
```

#### `user_vocabularies` (User's saved words)
```sql
CREATE TABLE user_vocabularies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    dictionary_entry_id BIGINT UNSIGNED NOT NULL,
    source_story_id BIGINT UNSIGNED NULL,    -- which story they saved it from
    source_sentence_id BIGINT UNSIGNED NULL,
    user_note TEXT NULL,                      -- personal notes
    created_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dictionary_entry_id) REFERENCES dictionary_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (source_story_id) REFERENCES stories(id) ON DELETE SET NULL,
    UNIQUE INDEX idx_user_word (user_id, dictionary_entry_id),
    INDEX idx_user_created (user_id, created_at)
);
```

#### `srs_cards` (Spaced Repetition Cards)
```sql
CREATE TABLE srs_cards (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    dictionary_entry_id BIGINT UNSIGNED NOT NULL,
    user_vocabulary_id BIGINT UNSIGNED NOT NULL,
    
    -- SM-2 Algorithm Fields
    card_state ENUM('new', 'learning', 'review', 'relearning') DEFAULT 'new',
    ease_factor DECIMAL(4,2) DEFAULT 2.50,   -- starting ease (SM-2 default)
    interval_days INT UNSIGNED DEFAULT 0,     -- current interval in days
    repetitions INT UNSIGNED DEFAULT 0,       -- successful review count
    lapses INT UNSIGNED DEFAULT 0,            -- times "Again" was pressed after graduating
    
    -- Learning step tracking
    learning_step TINYINT UNSIGNED DEFAULT 0, -- current step in learning steps [1min, 10min]
    
    -- Scheduling
    due_at TIMESTAMP NOT NULL,                -- when this card is next due
    last_reviewed_at TIMESTAMP NULL,
    graduated_at TIMESTAMP NULL,              -- when card first left "learning"
    
    -- Card type
    card_type ENUM('recognition', 'recall', 'listening') DEFAULT 'recognition',
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dictionary_entry_id) REFERENCES dictionary_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (user_vocabulary_id) REFERENCES user_vocabularies(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_entry_type (user_id, dictionary_entry_id, card_type),
    INDEX idx_due (user_id, card_state, due_at),
    INDEX idx_review_queue (user_id, due_at)
);
```

#### `srs_review_logs` (Review history for analytics)
```sql
CREATE TABLE srs_review_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    srs_card_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,        -- 1=Again, 2=Hard, 3=Good, 4=Easy
    previous_state ENUM('new', 'learning', 'review', 'relearning'),
    new_state ENUM('new', 'learning', 'review', 'relearning'),
    previous_interval INT UNSIGNED,
    new_interval INT UNSIGNED,
    previous_ease DECIMAL(4,2),
    new_ease DECIMAL(4,2),
    time_taken_ms INT UNSIGNED NULL,          -- how long user took to answer
    reviewed_at TIMESTAMP NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (srs_card_id) REFERENCES srs_cards(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, reviewed_at),
    INDEX idx_card (srs_card_id, reviewed_at)
);
```

#### `reading_progress`
```sql
CREATE TABLE reading_progress (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    story_id BIGINT UNSIGNED NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    last_sentence_position SMALLINT UNSIGNED DEFAULT 0,
    words_saved INT UNSIGNED DEFAULT 0,       -- count of words saved from this story
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    time_spent_seconds INT UNSIGNED DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_story (user_id, story_id),
    INDEX idx_user_status (user_id, status)
);
```

#### `categories`
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name_id VARCHAR(100) NOT NULL,           -- Kehidupan Sehari-hari
    name_en VARCHAR(100) NULL,               -- Daily Life
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) NULL,                   -- emoji or icon name
    sort_order SMALLINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### `category_story` (Pivot table)
```sql
CREATE TABLE category_story (
    story_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    
    PRIMARY KEY (story_id, category_id),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

#### `user_preferences`
```sql
CREATE TABLE user_preferences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    
    -- Reader preferences
    show_pinyin BOOLEAN DEFAULT TRUE,
    show_translation BOOLEAN DEFAULT FALSE,
    font_size ENUM('small', 'medium', 'large', 'xlarge') DEFAULT 'medium',
    reading_mode ENUM('full', 'sentence', 'focus') DEFAULT 'full',
    character_set ENUM('simplified', 'traditional') DEFAULT 'simplified',
    
    -- SRS preferences
    new_cards_per_day SMALLINT UNSIGNED DEFAULT 20,
    max_reviews_per_day SMALLINT UNSIGNED DEFAULT 100,
    card_order ENUM('new_first', 'review_first', 'mixed') DEFAULT 'mixed',
    
    -- Notification preferences
    daily_reminder BOOLEAN DEFAULT FALSE,
    reminder_time TIME DEFAULT '09:00:00',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `subscriptions` (Payment/Premium tracking)
```sql
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    plan ENUM('monthly', 'yearly') NOT NULL,
    status ENUM('active', 'cancelled', 'expired', 'past_due') DEFAULT 'active',
    midtrans_order_id VARCHAR(255) NULL,
    midtrans_transaction_id VARCHAR(255) NULL,
    payment_method VARCHAR(100) NULL,        -- gopay, ovo, bank_transfer, credit_card
    amount INT UNSIGNED NOT NULL,             -- in IDR (Rupiah)
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_expires (expires_at, status)
);
```

---

## 4. Key Laravel Routes Structure

```php
// Public (Guest)
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/stories', [LibraryController::class, 'index'])->name('library');
Route::get('/stories/{story:slug}', [LibraryController::class, 'show'])->name('story.preview');

// Auth
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Reader
    Route::get('/read/{story:slug}', [ReaderController::class, 'show'])->name('reader');
    Route::post('/read/{story}/progress', [ReaderController::class, 'updateProgress']);
    
    // Vocabulary
    Route::get('/vocabulary', [VocabularyController::class, 'index'])->name('vocabulary');
    Route::post('/vocabulary', [VocabularyController::class, 'store']);
    Route::delete('/vocabulary/{vocabulary}', [VocabularyController::class, 'destroy']);
    
    // Word lookup (API-like, returns JSON via Inertia)
    Route::get('/api/dictionary/{word}', [DictionaryController::class, 'lookup']);
    
    // SRS Review
    Route::get('/review', [ReviewController::class, 'index'])->name('review');
    Route::get('/review/session', [ReviewController::class, 'session'])->name('review.session');
    Route::post('/review/grade', [ReviewController::class, 'grade']);
    
    // Profile & Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::patch('/settings', [SettingsController::class, 'update']);
    
    // Premium / Subscription
    Route::get('/premium', [SubscriptionController::class, 'index'])->name('premium');
    Route::post('/premium/checkout', [SubscriptionController::class, 'checkout']);
    Route::post('/premium/webhook', [SubscriptionController::class, 'webhook'])
         ->withoutMiddleware('auth'); // Midtrans callback
});
```

---

## 5. Key Vue Components Structure

```
resources/js/
├── Pages/
│   ├── Landing.vue              // Marketing page
│   ├── Dashboard.vue            // User home
│   ├── Library/
│   │   ├── Index.vue            // Story browser
│   │   └── Show.vue             // Story preview (before reading)
│   ├── Reader/
│   │   └── Show.vue             // Interactive reading view
│   ├── Review/
│   │   ├── Index.vue            // Review overview (cards due)
│   │   └── Session.vue          // Active SRS session
│   ├── Vocabulary/
│   │   └── Index.vue            // Word bank
│   ├── Premium/
│   │   └── Index.vue            // Pricing page
│   └── Profile/
│       └── Edit.vue
│
├── Components/
│   ├── Reader/
│   │   ├── SentenceBlock.vue    // Single sentence with pinyin/translation
│   │   ├── WordPopup.vue        // Tap-to-translate popup
│   │   ├── ReaderControls.vue   // Pinyin/translation/font toggles
│   │   ├── ReadingProgress.vue  // Progress bar
│   │   └── AudioButton.vue      // TTS play button
│   ├── SRS/
│   │   ├── FlashCard.vue        // Card front/back with flip animation
│   │   ├── RatingButtons.vue    // Again/Hard/Good/Easy
│   │   ├── SessionProgress.vue  // Cards remaining
│   │   └── ReviewSummary.vue    // Post-session stats
│   ├── Library/
│   │   ├── StoryCard.vue        // Story thumbnail in grid
│   │   ├── LevelFilter.vue      // HSK level filter pills
│   │   └── CategoryFilter.vue
│   ├── Dashboard/
│   │   ├── ReviewWidget.vue     // "23 cards due" CTA
│   │   ├── StreakDisplay.vue    // Streak counter + calendar
│   │   └── SuggestedStories.vue
│   └── Shared/
│       ├── PinyinText.vue       // Renders character + pinyin ruby text
│       ├── HskBadge.vue         // HSK level badge
│       ├── PremiumGate.vue      // Premium upsell modal
│       └── AudioPlayer.vue
│
├── Composables/
│   ├── useReader.js             // Reader state, word selection, progress
│   ├── useSRS.js                // SRS card queue, grading logic
│   ├── useVocabulary.js         // Vocab save/delete
│   ├── useAudio.js              // TTS playback
│   ├── usePreferences.js        // User settings (pinyin, font, etc.)
│   └── useSubscription.js       // Premium status checks
│
└── Layouts/
    ├── AppLayout.vue            // Authenticated layout with nav
    ├── GuestLayout.vue          // Marketing/auth pages
    └── ReaderLayout.vue         // Minimal layout for reading (no nav distractions)
```

---

## 6. SRS Algorithm (SM-2 Implementation)

```php
// app/Services/SRSService.php — Core grading logic

class SRSService
{
    // Learning steps in minutes
    const LEARNING_STEPS = [1, 10];
    
    // Graduating interval (first review interval after learning)
    const GRADUATING_INTERVAL = 1; // days
    
    // Easy bonus multiplier
    const EASY_BONUS = 1.3;
    
    // Minimum ease factor
    const MIN_EASE = 1.30;
    
    // Interval modifier (global multiplier, can be user-configurable)
    const INTERVAL_MODIFIER = 1.0;

    public function gradeCard(SrsCard $card, int $rating): SrsCard
    {
        // rating: 1=Again, 2=Hard, 3=Good, 4=Easy
        
        return match($card->card_state) {
            'new', 'learning' => $this->gradeLearning($card, $rating),
            'review'          => $this->gradeReview($card, $rating),
            'relearning'      => $this->gradeRelearning($card, $rating),
        };
    }
    
    // See full implementation in codebase...
    // Key formulas:
    // new_interval = old_interval * ease_factor * INTERVAL_MODIFIER
    // new_ease = old_ease + (0.1 - (5 - rating) * (0.08 + (5 - rating) * 0.02))
}
```

---

## 7. Dictionary Data Pipeline

### Initial Setup
1. Download CC-CEDICT (open-source, ~120K entries)
2. Parse into structured format
3. Add HSK level tags from official HSK word lists
4. Generate Indonesian translations (batch via AI or professional translator)
5. Import into `dictionary_entries` table
6. Generate audio for top 5000 words (TTS)

### Word Segmentation Pipeline (for new stories)
```
Raw Chinese text
       │
       ▼
   jieba/pkuseg word segmentation
       │
       ▼
   Match each word to dictionary_entries
       │
       ▼
   Create sentence_words records
       │
       ▼
   Calculate story difficulty (HSK distribution)
       │
       ▼
   Generate pinyin (pypinyin)
       │
       ▼
   Ready for reader
```

### Recommended Approach for MVP
- Use a Python microservice or Laravel command for Chinese NLP (jieba)
- Call via `Symfony\Process` or queue job
- Alternatively: use a Node.js library (nodejieba) callable from Laravel

---

## 8. Deployment Architecture (MVP)

```
┌────────────────────────────────────────────┐
│            DigitalOcean / AWS               │
│                                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐ │
│  │  Nginx   │  │  PHP-FPM │  │  MySQL 8 │ │
│  │  (Proxy) │→ │  Laravel  │→ │          │ │
│  └──────────┘  └──────────┘  └──────────┘ │
│                      │                     │
│                      ▼                     │
│                ┌──────────┐                │
│                │  Redis   │                │
│                │(Cache/Q) │                │
│                └──────────┘                │
│                                            │
│  ┌──────────┐                              │
│  │ Worker   │  (Queue: TTS, AI, imports)   │
│  │ Process  │                              │
│  └──────────┘                              │
└────────────────────────────────────────────┘
         │
         ▼
┌────────────────┐
│ S3 / DO Spaces │  (Audio, images, exports)
└────────────────┘
```

### Quick Deploy Option
- **Laravel Forge** or **Ploi** for managed Laravel deployment
- Single server is fine for MVP (can handle 10K+ users)
- Scale to load balancer + separate DB when needed
