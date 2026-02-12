# User Journey Map
# MandarinID — Key User Flows

---

## Journey 1: New User → First Reading Session

```
┌─────────────┐    ┌──────────────┐    ┌─────────────┐    ┌──────────────┐
│ Landing Page │───→│  Register    │───→│ Select HSK  │───→│  Dashboard   │
│ (Marketing)  │    │ (Email/Google)│    │   Level     │    │ (First Time) │
└─────────────┘    └──────────────┘    └─────────────┘    └──────┬───────┘
                                                                  │
                                                                  ▼
┌─────────────┐    ┌──────────────┐    ┌─────────────┐    ┌──────────────┐
│  Save Words │←───│   Reading    │←───│ Open Story  │←───│Story Library │
│  to Vocab   │    │   (Reader)   │    │             │    │ (Browse/HSK) │
└──────┬──────┘    └──────────────┘    └─────────────┘    └──────────────┘
       │
       ▼
┌──────────────┐    ┌──────────────┐
│ Finish Story │───→│ Review Prompt│───→ "Review 8 new words now?"
│              │    │              │     [Yes → SRS] [Later → Dashboard]
└──────────────┘    └──────────────┘
```

### Step-by-Step

| Step | Page | User Action | System Response |
|------|------|-------------|-----------------|
| 1 | Landing | Clicks "Mulai Belajar" (Start Learning) | Show registration form |
| 2 | Register | Signs up with Google or email | Create account, send welcome email |
| 3 | Onboarding | Selects HSK level (or "I'm a beginner") | Set initial level, personalize recommendations |
| 4 | Dashboard | Sees welcome state with suggested stories | Show 3 recommended stories for their level |
| 5 | Library | Browses stories, clicks one | Navigate to reader |
| 6 | Reader | Reads text, taps unknown words | Show word popup with pinyin + meaning |
| 7 | Reader | Clicks "Simpan" (Save) on word popup | Word added to vocabulary, visual confirmation |
| 8 | Reader | Finishes story | Mark complete, show summary (words saved, time spent) |
| 9 | Post-read | Prompted to review saved words | Navigate to SRS review or return to dashboard |

### Key Moments & Emotions

| Moment | Emotion Target | Design Implication |
|--------|---------------|-------------------|
| First word popup | "Oh, this is easy!" | Must be instant (<200ms), clean, clear |
| Saving first word | Accomplishment | Satisfying micro-animation, toast confirmation |
| Finishing first story | Pride | Celebration UI, stats summary |
| First SRS review | Curiosity | Simple, non-intimidating, explain the system briefly |
| Premium gate hit | Mild frustration → desire | Show value clearly, offer trial |

---

## Journey 2: Daily Returning User (Core Loop)

```
┌─────────────┐    ┌──────────────┐    ┌──────────────┐
│   Login /    │───→│  Dashboard   │───→│ SRS Review   │
│   Return     │    │ "23 cards    │    │ (15-20 min)  │
│              │    │  due today"  │    │              │
└─────────────┘    └──────────────┘    └──────┬───────┘
                                              │
                          ┌───────────────────┘
                          ▼
                   ┌──────────────┐    ┌──────────────┐
                   │ Review Done  │───→│ Read a Story │
                   │ Summary      │    │ (Optional)   │
                   │ "Great job!" │    │              │
                   └──────────────┘    └──────┬───────┘
                                              │
                                              ▼
                                       ┌──────────────┐
                                       │ Save More    │
                                       │ Words → SRS  │
                                       │ Queue Grows  │
                                       └──────────────┘
```

### Daily Loop Detail

| Step | Action | Duration | Notes |
|------|--------|----------|-------|
| 1 | Open site, see dashboard | 10 sec | Show cards due prominently |
| 2 | Start SRS review | 10-20 min | Core retention activity |
| 3 | See review summary | 30 sec | Streak count, accuracy, encouragement |
| 4 | (Optional) Browse new story | 2 min | Suggested based on level + interests |
| 5 | Read story, save new words | 10-30 min | Words enter SRS queue for tomorrow |
| 6 | Close | — | Streak maintained |

### Engagement Hooks
- **Streak counter** visible on dashboard (consecutive days with review)
- **Cards due** is the primary CTA on dashboard
- **"Kamu belum review hari ini!"** (You haven't reviewed today!) reminder option
- New story notifications for their level

---

## Journey 3: Reading a Story (Detailed Reader Flow)

```
┌─────────────────────────────────────────────────────┐
│                    READER VIEW                       │
│                                                      │
│  ┌─── Story Header ───────────────────────────────┐ │
│  │ 小猫找朋友 (Xiǎo Māo Zhǎo Péngyou)           │ │
│  │ Kucing Kecil Mencari Teman | HSK 1 | 5 min     │ │
│  └────────────────────────────────────────────────┘ │
│                                                      │
│  ┌─── Reading Controls ──────────────────────────┐  │
│  │ [Pinyin: ON/OFF] [Translation: ON/OFF]        │  │
│  │ [Font: A A A] [Mode: Full/Focus/Sentence]     │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ┌─── Sentence Block ────────────────────────────┐  │
│  │  xiǎo māo hěn kě ài                          │  │ ← Pinyin (toggleable)
│  │  小猫 很 可爱。                                │  │ ← Chinese text (tappable words)
│  │  Kucing kecil sangat lucu.                    │  │ ← Indonesian (toggleable)
│  │                                         🔊    │  │ ← Audio button
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ┌─── Word Popup (on tap) ───────────────────────┐  │
│  │  可爱  kě ài                            🔊    │  │
│  │  HSK 2                                        │  │
│  │  Lucu, menggemaskan (cute, adorable)          │  │
│  │  Example: 这个孩子很可爱                       │  │
│  │              [💾 Simpan ke Kosakata]           │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ┌─── Progress Bar ──────────────────────────────┐  │
│  │  ████████░░░░░░░░  12/30 sentences            │  │
│  └────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Reader Interaction Matrix

| User Action | System Response | State Change |
|-------------|----------------|--------------|
| Tap/click a word | Show word popup with details | — |
| Click "Simpan" in popup | Save to vocabulary, show toast | Word added to user vocab + SRS queue |
| Click popup again (already saved) | Show popup with "Sudah disimpan ✓" | — |
| Toggle pinyin ON | Show pinyin above all characters | Persist preference |
| Toggle pinyin OFF | Hide pinyin | Persist preference |
| Toggle translation ON | Show Indonesian below each sentence | Persist preference |
| Click sentence audio 🔊 | Play TTS for that sentence | — |
| Scroll past sentence | Mark sentence as "seen" | Update reading position |
| Click "Next" (sentence mode) | Reveal next sentence | Update progress |
| Reach end of story | Show completion screen | Mark story complete |
| Click back/navigate away | Save current position | Can resume later |

---

## Journey 4: SRS Review Session

```
┌──────────────────────────────────────────────────────┐
│                   SRS REVIEW                          │
│                                                       │
│  Session: 23 cards due | 5 new | 18 review            │
│                                                       │
│  ┌─── Card Front ────────────────────────────────┐   │
│  │                                                │   │
│  │                    认识                         │   │
│  │                                                │   │
│  │              [ Tampilkan Jawaban ]              │   │
│  │              (Show Answer)                     │   │
│  └────────────────────────────────────────────────┘   │
│                                                       │
│                    ▼ (after flip)                      │
│                                                       │
│  ┌─── Card Back ─────────────────────────────────┐   │
│  │                                                │   │
│  │    认识  rèn shi                        🔊     │   │
│  │    Kenal, mengenal (to know/recognize)         │   │
│  │                                                │   │
│  │    Example: 我认识他。                          │   │
│  │    (Saya mengenal dia.)                        │   │
│  │                                                │   │
│  │    From: "小猫找朋友" (HSK 1)                   │   │
│  │                                                │   │
│  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐         │   │
│  │  │Lagi  │ │Susah │ │Bagus │ │Mudah │         │   │
│  │  │<1m   │ │ 5m   │ │ 1d   │ │ 4d   │         │   │
│  │  │Again │ │Hard  │ │Good  │ │Easy  │         │   │
│  │  └──────┘ └──────┘ └──────┘ └──────┘         │   │
│  └────────────────────────────────────────────────┘   │
│                                                       │
│  Progress: ████████████░░░  18/23                      │
└──────────────────────────────────────────────────────┘

                    ▼ (after all cards)

┌──────────────────────────────────────────────────────┐
│               REVIEW COMPLETE 🎉                      │
│                                                       │
│  Reviewed: 23 cards                                   │
│  Accuracy: 78% (18/23 correct on first try)           │
│  Time: 12 minutes                                     │
│  Streak: 7 days 🔥                                    │
│                                                       │
│  [Baca Cerita Baru]  [Kembali ke Dashboard]           │
│  (Read New Story)    (Back to Dashboard)              │
└──────────────────────────────────────────────────────┘
```

### SRS State Machine

```
              ┌──────────┐
              │   NEW    │
              │ (Unseen) │
              └────┬─────┘
                   │ First review
                   ▼
              ┌──────────┐
         ┌───→│ LEARNING │←──── Again
         │    │(Steps: 1m,│
         │    │  10m)     │
         │    └────┬─────┘
         │         │ Pass all learning steps
         │         ▼
         │    ┌──────────┐
         │    │  REVIEW  │←──── Hard/Good (adjust interval)
         │    │(Graduated)│
         │    └────┬─────┘
         │         │
    Lapse │        │ Easy (bonus interval)
  (Again) │        ▼
         │    ┌──────────┐
         └────│ MATURE   │  (interval > 21 days)
              │          │
              └──────────┘
```

---

## Journey 5: Free → Premium Conversion

### Touchpoints for Premium Prompt

| Trigger | Context | Message |
|---------|---------|---------|
| Story locked | User clicks premium story | "Cerita ini untuk member Premium. Upgrade untuk akses 200+ cerita!" |
| Vocab limit hit | User tries to save 51st word | "Kamu sudah menyimpan 50 kata. Upgrade untuk simpan tanpa batas!" |
| After 7-day streak | Dashboard | "Kamu sudah belajar 7 hari berturut-turut! 🔥 Unlock semua fitur?" |
| Feature discovery | Clicks advanced SRS settings | "Pengaturan lanjutan tersedia untuk Premium" |

### Conversion Flow

```
┌────────────┐    ┌───────────────┐    ┌──────────────┐    ┌─────────────┐
│  Hit Free  │───→│ Premium Promo │───→│  Pricing     │───→│  Payment    │
│  Limit     │    │  Modal        │    │  Page        │    │ (Midtrans)  │
└────────────┘    └───────────────┘    └──────────────┘    └──────┬──────┘
                                                                  │
                                       ┌──────────────┐          │
                                       │  Welcome to  │←─────────┘
                                       │  Premium! 🎉 │
                                       │  Full access  │
                                       └──────────────┘
```

### Pricing Page Structure
- Monthly: Rp 99.000/bulan
- Yearly: Rp 79.000/bulan (Rp 948.000/tahun) — "Hemat 20%"
- Feature comparison table (Free vs Premium)
- Testimonials (once available)
- 7-day money-back guarantee

---

## Journey 6: Content Creation (Admin Flow)

```
┌─────────────┐    ┌──────────────┐    ┌──────────────┐
│ Admin Panel │───→│ Create Story │───→│ Write/Paste  │
│ (Filament)  │    │              │    │ Chinese Text  │
└─────────────┘    └──────────────┘    └──────┬───────┘
                                              │
                                              ▼
┌─────────────┐    ┌──────────────┐    ┌──────────────┐
│  Publish    │←───│ Review &     │←───│ Auto-Process │
│  (Free or   │    │ Edit         │    │ • Segment    │
│   Premium)  │    │              │    │ • Tag HSK    │
└─────────────┘    └──────────────┘    │ • Add Pinyin │
                                       │ • Translate  │
                                       └──────────────┘
```

### Admin Story Creation Steps

| Step | Action | Automation |
|------|--------|-----------|
| 1 | Enter/paste Chinese text | — |
| 2 | Auto-segment into words | jieba/pkuseg word segmentation |
| 3 | Auto-detect HSK level per word | Lookup against HSK word list |
| 4 | Calculate overall story HSK level | Based on % of vocab at each level |
| 5 | Auto-generate pinyin | pypinyin or similar |
| 6 | Add/edit Indonesian translations per sentence | AI-assisted + human edit |
| 7 | Set metadata (title, category, thumbnail, free/premium) | Manual |
| 8 | Preview in reader view | Built-in preview |
| 9 | Publish | Toggle live |

### AI-Assisted Story Generation

| Step | Action |
|------|--------|
| 1 | Admin selects target HSK level + topic + approximate length |
| 2 | System prompts Claude API with HSK vocabulary constraints |
| 3 | AI generates story draft |
| 4 | Auto-process (segment, tag, grade) |
| 5 | Admin reviews, edits, adjusts |
| 6 | Add Indonesian translations (AI-assisted) |
| 7 | Final review and publish |
