# Multi-Domain Single Codebase — Japanese Language Expansion

## Context

We have a Mandarin graded reader web app (nihao.id) built with Laravel + Filament. We want to add Japanese graded readers as a second product, but serve it from a separate domain with different branding. Both domains run from the same codebase, same database, same admin panel.

**Why two domains:** The brand "Nihao" is inherently Chinese. Japanese learners (mostly anime/manga fans in Indonesia) won't trust a Chinese-named app for Japanese learning. A separate domain like yonde.id (読んで = "read!") with Japanese-feeling branding solves this.

**Why one codebase:** Solo founder. Can't maintain two separate apps. Need one admin, one database, one deployment, one payment system.

---

## Architecture Overview

### Domain Routing

Two domains point to the same Laravel app. A middleware detects the domain and sets the brand context for the entire request lifecycle.

```
nihao.id → Brand: mandarin
yonde.id → Brand: japanese
```

The brand context should be available everywhere: controllers, views, Blade components, routes, and Filament admin.

### Brand Configuration

Create a config file or service that holds per-brand settings:

```
brands:
  mandarin:
    domain: nihao.id
    name: Nihao
    language_code: zh
    tagline: "Belajar Mandarin dengan membaca cerita"
    levels_system: HSK (HSK 1-6)
    primary_color: "#f59e0b" (amber)
    logo: nihao-logo.svg
    
  japanese:
    domain: yonde.id
    name: Yonde
    language_code: ja
    tagline: "Belajar Bahasa Jepang dengan membaca cerita"
    levels_system: JLPT (N5-N1)
    primary_color: "#f472b6" (pink/sakura)
    logo: yonde-logo.svg
```

### Middleware Implementation

Create a `DetectBrand` middleware that:
1. Reads the request host
2. Matches to a brand config
3. Sets `app('brand')` or a singleton `BrandContext` service
4. Makes brand available to all Blade views via a view composer or share

This middleware should run on every web request (add to the web middleware group).

For local development, support brand detection via:
- Subdomain (mandarin.localhost / japanese.localhost)
- Or a query param/session override for easier testing
- Or .env default brand

---

## Database Changes

### Stories Table

Add a `language` column (or keep using an existing column if one exists) to differentiate content:

```
stories:
  - id
  - language: enum('zh', 'ja')  ← NEW or modify existing
  - level: string (e.g., 'HSK 1', 'JLPT N5')
  - title
  - content
  - is_free: boolean
  - ... (existing columns)
```

The `level` field already exists for HSK. It should work for JLPT too since both are string-based. If level is currently an integer or tied to a specific HSK enum, it needs to be refactored.

### Level Mapping

```
Mandarin:        Japanese:
HSK 1 (free)     JLPT N5 (free)
HSK 2            JLPT N4
HSK 3            JLPT N3
HSK 4            JLPT N2
HSK 5            JLPT N1
HSK 6
```

Note: JLPT goes from N5 (easiest) to N1 (hardest) — reverse numbering from HSK.

### Plans Table

Add language-specific plans or add a `language` column to existing plans:

```
plans:
  - id
  - language: enum('zh', 'ja', 'bundle')  ← NEW
  - slug: string
  - label: string (e.g., "3 Bulan - Mandarin", "3 Bulan - 日本語")
  - duration_months: integer
  - price: integer
  - is_founder: boolean
  - ... (existing columns)
```

New plans to create:
| Plan | Language | Price | Duration |
|------|----------|-------|----------|
| 3 Bulan - Japanese | ja | 149,000 | 3 |
| 6 Bulan - Japanese | ja | 249,000 | 6 |
| 1 Tahun - Japanese | ja | 399,000 | 12 |
| Bundle 3 Bulan | bundle | 249,000 | 3 |
| Bundle 6 Bulan | bundle | 399,000 | 6 |
| Bundle 1 Tahun | bundle | 649,000 | 12 |

No founder edition for Japanese.

Bundle plans give access to both languages. Individual plans give access to one language only.

### Subscriptions Table

The existing subscriptions table should already work. Each subscription references a plan, and the plan has a language. When checking access:

```
- User has active 'zh' subscription → can access Mandarin premium stories
- User has active 'ja' subscription → can access Japanese premium stories  
- User has active 'bundle' subscription → can access both
```

### Users Table

No changes needed. Users have one account across both domains. They log in on either domain with the same credentials. Session/auth should work across both domains (see Cross-Domain Auth section below).

---

## Cross-Domain Authentication

Users should be able to log in once and be recognized on both domains. Options:

### Option A: Shared Session Cookie (Recommended if both are subdomains)
If using subdomains like mandarin.app.id / japanese.app.id:
- Set session cookie domain to `.app.id`
- Both subdomains share the session automatically

### Option B: Token-Based Cross-Domain Login
If using separate TLDs (nihao.id and yonde.id):
- After login on nihao.id, generate a one-time token
- Redirect to yonde.id/auth/cross-login?token=xxx
- yonde.id validates the token and creates a session
- Show a "Kamu juga punya akun di Yonde →" link in the dashboard

### Option C: Keep Sessions Separate
Simplest approach. Users log in separately on each domain. Same email/password works since it's the same database. They just need to log in twice. This is fine for launch — cross-domain SSO can come later.

**Recommendation:** Start with Option C. It's zero extra work. Add cross-domain linking later if users complain.

---

## Frontend / Blade Theming

### Layout Changes

The main layout should dynamically apply brand theming:

```blade
{{-- Use brand context to set theme --}}
<body class="brand-{{ brand()->key }}">

{{-- Logo --}}
<img src="{{ brand()->logo }}" />

{{-- Brand name --}}
<h1>{{ brand()->name }}</h1>

{{-- Tagline --}}
<p>{{ brand()->tagline }}</p>
```

### CSS Theming

Use CSS variables or Tailwind config per brand:

```css
.brand-mandarin {
  --color-primary: #f59e0b;
  --color-primary-light: #fbbf24;
  /* warm amber theme */
}

.brand-japanese {
  --color-primary: #f472b6;
  --color-primary-light: #f9a8d4;
  /* sakura pink theme */
}
```

### Content Filtering

All story listings, search, and browse pages should automatically filter by the current brand's language. The user should never see Japanese stories on nihao.id or Mandarin stories on yonde.id.

```php
// In controllers/queries
Story::where('language', brand()->language_code)->get();
```

### Pricing Page

Each domain shows only its own language plans + bundle option:

**On nihao.id:**
- 3 Bulan Mandarin — Rp149,000
- 6 Bulan Mandarin — Rp249,000
- 1 Tahun Mandarin — Rp399,000
- 🎁 Bundle (Mandarin + Japanese) — Rp249,000 / 3 bulan (hemat 16%)

**On yonde.id:**
- 3 Bulan Japanese — Rp149,000
- 6 Bulan Japanese — Rp249,000
- 1 Tahun Japanese — Rp399,000
- 🎁 Bundle (Japanese + Mandarin) — Rp249,000 / 3 bulan (hemat 16%)

The bundle cross-sells the other language on each domain.

---

## Filament Admin Changes

### Stories Resource

Add a `language` filter to the stories list. When creating/editing a story, require selecting the language. The level dropdown should change based on language selection (HSK levels for zh, JLPT levels for ja).

### Plans Resource

Add language column. Show all plans across both languages in one table.

### Dashboard Widgets

The existing analytics widgets should be updated to:
1. Show combined revenue by default
2. Allow filtering by language (Mandarin / Japanese / All)
3. The revenue bar chart should stack by language (amber for Mandarin, pink for Japanese)
4. Active users widget should show per-language breakdown

### Navigation

Consider adding a brand indicator in the Filament sidebar or header so the admin always knows they're managing both brands from one panel.

---

## SEO & Marketing Separation

Each domain needs its own:
- Landing page with language-specific copy and design
- Meta tags, OG images, favicon
- Google Analytics property (or use one GA4 with separate streams)
- TikTok account (@nihao.id and @yonde.id)
- Facebook page / ad account

The content on each domain should feel like a completely separate product to the end user. Only the admin panel and payment confirmation emails reveal they're connected.

---

## Migration / Implementation Order

### Phase 1: Prepare Codebase (before Japanese launch)
1. Create BrandContext service and DetectBrand middleware
2. Add `language` column to stories table (default 'zh' for existing data)
3. Add `language` column to plans table (default 'zh' for existing data)
4. Update all story queries to filter by brand language
5. Set up Blade theming with CSS variables
6. Test everything still works on nihao.id (should be zero visible change)

### Phase 2: Add Japanese Content
7. Create Japanese plans in database
8. Create bundle plans in database
9. Start adding JLPT N5 stories (aim for ~40 before launch)
10. Update Filament admin with language filters

### Phase 3: Launch yonde.id
11. Register yonde.id domain
12. Point DNS to same server
13. Configure web server (Nginx/Apache) to serve both domains
14. Update Laravel trusted proxies / allowed hosts if needed
15. Create Japanese landing page and pricing page
16. Test payment flow end-to-end on yonde.id
17. Soft launch to existing nihao.id users who might want Japanese
18. Public launch with TikTok content blitz

### Phase 4: Cross-Domain Features (later)
19. Cross-domain login (if needed)
20. "You also have access to Yonde →" dashboard link for bundle users
21. Cross-selling banners ("Juga belajar Mandarin? Cek nihao.id!")

---

## Bundle Pricing Strategy

| Bundle | Price | vs Separate | Savings |
|--------|-------|-------------|---------|
| 3 Bulan Both | Rp249,000 | Rp298,000 | 16% |
| 6 Bulan Both | Rp399,000 | Rp498,000 | 20% |
| 1 Tahun Both | Rp649,000 | Rp798,000 | 19% |

Bundle access check logic:
```php
function canAccessPremium(User $user, string $language): bool
{
    return $user->activeSubscriptions()
        ->whereHas('plan', function ($q) use ($language) {
            $q->where('language', $language)
              ->orWhere('language', 'bundle');
        })
        ->exists();
}
```

---

## Key Technical Decisions to Discuss

1. **Brand detection:** Middleware approach vs route prefix vs config-based? Middleware on domain seems cleanest.

2. **Level system:** Should levels be a separate `levels` table with language FK, or keep as a simple string on stories? Table is cleaner long-term if you add more languages.

3. **Shared vs separate TikTok pixel / GA tracking:** Recommend separate GA4 data streams but same property, so you can see combined + per-brand analytics.

4. **Email templates:** Should transactional emails (welcome, payment confirmation, expiry reminder) be branded per domain? Yes — use brand context in mail templates.

5. **Future languages:** If you add Korean, Thai, etc. later, does this architecture scale? Yes — just add another brand config entry and domain. The language column on stories/plans handles it.
