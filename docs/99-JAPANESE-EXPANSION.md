  Recommendation: Same codebase, same product                                                                         
                                                                                                                      
  Your architecture already has a clean separation between language-agnostic and Chinese-specific code. This makes    
  expanding within the same codebase very feasible.                                                                   
                                                                                                                      
  What's already generic (no changes needed)                                                                          
                                                                                                                      
  - SRS engine (SrsService) - pure SM-2 algorithm, language-independent
  - User vocabulary, reading progress, analytics                                                                      
  - Auth, payments (Midtrans), subscriptions                                                                          
  - Most frontend pages (Review, Vocabulary, Stats, Membership)                                                       
                                                                                                                      
  What's Chinese-specific (needs abstraction)                                                                         
                                                                                                                      
  - NLP services: ChineseSegmenter (jieba), ChineseSentenceSplitter, PinyinService — swap with MeCab/Janome for
  Japanese, romaji instead of pinyin
  - Dictionary schema: pinyin/HSK fields → romaji/JLPT for Japanese, plus kanji/hiragana/katakana columns
  - TTS config: cmn-CN voice → ja-JP voice
  - Dictionary import: CC-CEDICT format → JMdict format
  - Frontend: WordTooltip.vue (pinyin + HSK badge) and ReaderControls.vue (pinyin toggle)

  Why same codebase wins

  1. ~60-70% of the code is reusable as-is. Duplicating it means maintaining two copies of SRS, payments, auth,
  analytics, etc.
  2. The abstraction is straightforward. You'd introduce a strategy/interface pattern:
    - LanguageProcessor interface with implementations like ChineseProcessor, JapaneseProcessor
    - Each handles: segmentation, romanization, sentence splitting, difficulty levels
    - DictionaryEntry gets a language column; language-specific fields go into a JSON column or polymorphic tables
  3. Cross-selling opportunity. Users learning Chinese are prime candidates for Japanese (and vice versa). One
  account, one subscription, multiple languages — much better UX and business model.
  4. Shared infrastructure costs. One deployment, one CI/CD pipeline, one monitoring setup.

  Why a separate codebase would be worse

  - You'd fork and immediately start diverging. Bug fixes and feature improvements need to be applied twice.
  - The Chinese-specific code is maybe 30-35% of the total — not enough to justify a full fork.
  - Subscription/payment logic duplication is a maintenance nightmare.

  The one caveat

  If you envision Japanese as a fundamentally different product (different target audience, different pricing,
  different brand), then a separate deployment with a shared package/library for the generic parts could make sense.
  But architecturally, a single codebase with a language abstraction layer is cleaner.

  Rough migration path

  1. Add a language enum (chinese, japanese) to DictionaryEntry, Story, etc.
  2. Extract a LanguageProcessor interface from existing Chinese services
  3. Make WordTooltip.vue and ReaderControls.vue language-aware (show pinyin vs romaji, HSK vs JLPT)
  4. Add Japanese implementations: JapaneseSegmenter, RomajiService, JmdictParser
  5. Scope user preferences per language

  The existing code quality makes this very doable without a rewrite.