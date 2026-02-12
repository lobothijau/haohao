# Product Requirements Document (PRD)
# Nihao — Graded Reader & SRS Platform for Indonesian Learners

## 1. Product Overview

**Product Name:** Nihao (working title — adjust as needed)
**Tagline:** Belajar Mandarin dengan cara yang menyenangkan dan efektif
**Platform:** Web (Laravel 11 + Vue 3 + Inertia.js)
**Target Audience:** Indonesian speakers learning Mandarin Chinese
**Business Model:** Freemium (free stories + paid premium subscription)

### Problem Statement
Indonesian learners of Mandarin lack a dedicated, web-based platform that combines:
- Graded reading material with Bahasa Indonesia translations
- Interactive character/word lookup tailored for Indonesian speakers
- Spaced repetition vocabulary review (SRS) integrated with reading progress
- Content graded by HSK levels with progressive difficulty

Existing solutions (DuChinese, Mandarin Companion) are English-centric and mobile-only. There is no quality web-based graded reader for the Indonesian market.

### Key Differentiators
1. **Indonesian-first:** All translations, UI, and explanations in Bahasa Indonesia
2. **Web-based:** No app install required, works on any device
3. **Integrated SRS:** Words saved from reading flow directly into Anki-like review
4. **Cultural relevance:** Stories with Indonesian/Southeast Asian cultural context
5. **Hokkien/Mandarin connections:** Leverage shared vocabulary (many Indonesian words derive from Chinese dialects)

---

## 2. User Personas

### Persona 1: Rina (University Student)
- **Age:** 20, studying at a Jakarta university
- **Mandarin level:** HSK 2 (beginner-intermediate)
- **Motivation:** Career advantage, connecting with Chinese-Indonesian heritage
- **Pain points:** Textbooks are boring, existing apps are in English
- **Device:** Laptop + phone, prefers studying on laptop

### Persona 2: Budi (Working Professional)
- **Age:** 32, works in import/export with Chinese partners
- **Mandarin level:** HSK 3-4
- **Motivation:** Business communication, reading contracts/emails
- **Pain points:** No time for classes, needs flexible self-study
- **Device:** Desktop at work, tablet at home

### Persona 3: Mei (Chinese-Indonesian Heritage Learner)
- **Age:** 17, high school student
- **Mandarin level:** HSK 1 (can speak some but can't read)
- **Motivation:** Reconnecting with family language, understanding grandparents
- **Pain points:** Can understand spoken Mandarin but struggles with characters
- **Device:** Phone + shared family laptop

---

## 3. Feature Specifications

### 3.1 Graded Reader (Core Feature)

#### Story Library
- Browse stories filtered by HSK level (1-6), topic, length
- Each story shows: title (Chinese + pinyin + Indonesian), HSK level badge, estimated reading time, word count, % new words for user
- Story categories: daily life, culture, business, travel, folklore, news, Chinese-Indonesian stories
- Free tier: 3-5 free stories per level
- Premium: full library access

#### Interactive Reader View
- **Sentence-by-sentence display** with clear visual separation
- **Pinyin toggle:** Show/hide pinyin above characters (per-sentence or global)
- **Tap/click any word →** popup card showing:
  - Simplified character (+ traditional optional)
  - Pinyin with tone marks
  - Bahasa Indonesia meaning (primary)
  - English meaning (secondary, optional)
  - HSK level tag
  - Example sentence
  - Audio pronunciation
  - "Save to vocabulary" button
- **Sentence translation toggle:** Show/hide Indonesian translation per sentence
- **Audio playback:** TTS for individual words and full sentences
- **Reading modes:**
  - Full text mode (all sentences visible)
  - Sentence-by-sentence mode (progressive reveal, good for beginners)
  - Focus mode (current sentence highlighted, rest dimmed)
- **Font size adjustment** (small/medium/large/extra-large)
- **Character display:** Simplified default, toggle to Traditional

#### Reading Progress
- Track which stories have been read (not started / in progress / completed)
- Reading statistics: stories read, characters encountered, time spent

### 3.2 Vocabulary System

#### Word Bank
- All saved words from reading sessions
- Each word card shows: character, pinyin, meaning (ID), HSK level, source story
- Filter/sort by: HSK level, date added, mastery level, alphabetical (pinyin)
- Bulk actions: delete, export to CSV/Anki

#### Word Detail View
- Character breakdown (component radicals)
- Stroke order animation (optional, can use external API)
- Multiple meanings with example sentences
- Related words / compound words
- Audio pronunciation
- Personal notes field

### 3.3 SRS Review System (Anki-like)

#### Core SRS Algorithm
- Based on SM-2 algorithm (same foundation as Anki)
- Card states: New → Learning → Review → Graduated
- Review ratings: Again (1) → Hard (2) → Good (3) → Easy (4)
- Interval progression example:
  - Again: 1 min → 10 min
  - Good: 1 day → 3 days → 7 days → 14 days → 30 days → ...
  - Easy: Immediate graduation with extended interval
- Ease factor adjustment per card (starting at 2.5)

#### Review Interface
- **Front of card:** Character only (test recall)
- **Back of card:** Pinyin + Indonesian meaning + example sentence + audio
- **Card types:**
  - Recognition: See character → recall meaning
  - Recall: See meaning → recall character (optional, advanced)
  - Listening: Hear audio → recall character + meaning (optional)
- **Session settings:**
  - New cards per day: user-configurable (default 20)
  - Max reviews per day: user-configurable (default 100)
  - Card order: New first / Review first / Mixed
- **Review statistics:**
  - Cards due today
  - Daily streak
  - Accuracy rate
  - Forecast (upcoming review load)

#### Integration with Reader
- Words saved while reading are automatically queued as new SRS cards
- After finishing a story, prompt: "You saved X new words. Review them now?"
- Words encountered multiple times in reading have pre-boosted familiarity

### 3.4 User Accounts & Progress

#### Authentication
- Email/password registration
- Google OAuth - Social Login After MVP

#### User Profile
- Display name, avatar
- Current HSK level (self-reported or assessed)
- Learning streak (consecutive days with activity)
- Total words learned, stories read, review accuracy

#### Dashboard
- Daily review summary: cards due, new cards available
- Reading suggestions based on level
- Weekly/monthly progress charts
- Streak calendar (GitHub-style contribution graph)

### 3.5 Admin / Content Management

#### Story Management (Admin Panel)
- Create/edit stories with rich text editor
- Auto-segmentation of Chinese text into words (using jieba or similar)
- Auto-tagging of HSK levels per word
- Assign story metadata: level, category, tags, thumbnail
- AI-assisted story generation workflow:
  1. Set target HSK level + topic
  2. Generate draft via AI
  3. Human review + edit
  4. Auto-grade and tag
  5. Publish (free or premium)

#### Dictionary Management
- Master dictionary: character → pinyin → Indonesian meaning → HSK level
- Ability to add/edit entries
- Bulk import from CC-CEDICT + Indonesian translations

---

## 4. Monetization: Freemium Model

### Free Tier
- 3-5 stories per HSK level (rotating selection or fixed starter pack)
- Full reader functionality on free stories
- Save up to 50 words to vocabulary
- SRS review for saved words
- Basic progress tracking

### Premium Tier (Target: Rp 79,000–149,000/month)
- Full story library access (all levels, all categories)
- Unlimited vocabulary saves
- Advanced SRS statistics and customization
- Offline reading (future: PWA)
- Priority access to new stories
- Ad-free experience (if ads added to free tier later)
- Export vocabulary to Anki deck

### Payment Integration
- Midtrans (primary — supports Indonesian payment methods: GoPay, OVO, bank transfer, credit card)
- (Future: Google Pay, Apple Pay for international users)

---

## 5. Content Strategy

### Content Pipeline
1. **Seed content:** Curate/write 10-15 stories per HSK level (HSK 1-4 priority)
2. **AI-assisted generation:** Use Claude API to generate story drafts at target HSK levels
3. **Human review:** Native speaker review for naturalness and accuracy
4. **Indonesian translation:** Professional translation of all sentences
5. **Audio:** TTS generation for all content (Azure/Google TTS for Mandarin)
6. **Ongoing:** 2-4 new stories per week post-launch

### HSK Level Guidelines for Content
| Level | Unique Characters | Vocabulary | Sentence Length | Grammar |
|-------|------------------|------------|-----------------|---------|
| HSK 1 | ~170 | ~150 words | 3-6 words | Simple SVO, 是, 有, basic questions |
| HSK 2 | ~350 | ~300 words | 4-8 words | 了, 过, comparisons, 因为...所以 |
| HSK 3 | ~620 | ~600 words | 5-12 words | 把 structure, complements, more conjunctions |
| HSK 4 | ~1000 | ~1200 words | 6-15 words | Complex sentences, formal expressions |
| HSK 5 | ~1500 | ~2500 words | 8-20 words | Literary expressions, idioms |
| HSK 6 | ~2600 | ~5000 words | 10-25+ words | Near-native complexity |

### Dictionary Source
- Base: CC-CEDICT (open-source Chinese-English dictionary)
- Indonesian translations: Translated/adapted from English meanings
- HSK tagging: Official HSK word lists (2012 standard or new 2021 standard — decide)
- Enrichment: Add Indonesian-specific notes, Hokkien cognates where relevant

---

## 6. Non-Functional Requirements

### Performance
- Reader page load: < 2 seconds
- Word popup response: < 200ms
- SRS card flip: < 100ms
- Support 10,000 concurrent users

### SEO & Discovery
- Server-side rendered story pages (Inertia SSR)
- Story pages indexable with title, excerpt, level
- Blog/learning tips section for organic traffic

### Accessibility
- Keyboard navigation in reader
- Screen reader support for translations
- High contrast mode
- Responsive: desktop, tablet, mobile

### Localization
- UI language: Bahasa Indonesia (primary), English (secondary)
- All system messages in Bahasa Indonesia

---

## 7. MVP Scope & Phasing

### Phase 1: MVP (8-12 weeks)
- [ ] User auth (email + Google OAuth)
- [ ] Story library with browse/filter
- [ ] Interactive reader with tap-to-translate, pinyin toggle, sentence translation
- [ ] Basic TTS (word-level)
- [ ] Vocabulary saving from reader
- [ ] Basic SRS review (SM-2 algorithm)
- [ ] User dashboard with progress
- [ ] Admin panel for story management (Filament)
- [ ] 30-40 seed stories (HSK 1-3)
- [ ] Responsive web design
- [ ] Free/premium gating

### Phase 2: Enhancement (4-8 weeks after MVP)
- [ ] Full sentence audio TTS
- [ ] Reading modes (sentence-by-sentence, focus mode)
- [ ] SRS statistics and charts
- [ ] Payment integration (Midtrans)
- [ ] AI story generation pipeline in admin
- [ ] Story categories and tags
- [ ] Word detail view (radicals, stroke order)

### Phase 3: Growth (Ongoing)
- [ ] PWA for offline reading
- [ ] HSK 4-6 content
- [ ] Community features (comments, story ratings)
- [ ] Anki export
- [ ] WhatsApp OTP login
- [ ] Gamification (XP, badges, leaderboard)
- [ ] Mobile app (if justified by growth)
