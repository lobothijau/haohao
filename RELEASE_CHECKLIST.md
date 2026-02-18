# Nihao — Release Checklist

Complete guide to set up and deploy the nihao Chinese learning app from scratch.

---

## 1. Prerequisites

```bash
# Required
php --version    # PHP 8.2+
composer --version
node --version   # Node 16+
npm --version

# Python (for Chinese word segmentation)
python3 --version  # Python 3.8+
```

---

## 2. Clone & Install Dependencies

```bash
git clone <repo-url> nihao
cd nihao

# PHP dependencies
composer install

# Node dependencies
npm install

# Python virtual environment (for jieba word segmenter)
python3 -m venv .venv
source .venv/bin/activate
pip install -r scripts/requirements.txt
deactivate
```

---

## 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```env
# App
APP_NAME=Nihao
APP_ENV=local          # or 'production'
APP_DEBUG=true         # false in production
APP_URL=http://nihao.test

# Database (SQLite by default, no extra config needed)
DB_CONNECTION=sqlite

# Queue (jobs run in background)
QUEUE_CONNECTION=database

# Mail (use 'log' for local, configure SMTP/Postmark/SES for production)
MAIL_MAILER=log

# Google Analytics (optional)
GA_MEASUREMENT_ID=

# Google TTS (optional — requires GCP billing account)
# GOOGLE_TTS_CREDENTIALS_PATH=
# GOOGLE_TTS_VOICE=cmn-CN-Neural2-A
# GOOGLE_TTS_LANGUAGE_CODE=cmn-CN
# GOOGLE_TTS_SPEAKING_RATE=1.0
```

---

## 4. Database

```bash
# Create SQLite database file
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed data (roles, categories, dictionary 120K+ entries, sample stories)
php artisan db:seed
```

**Seeder creates:**
- Roles: `admin`, `user`
- 10 story categories (Kehidupan Sehari-hari, Makanan, Perjalanan, etc.)
- 120K+ dictionary entries from `database/dictionary_entries.sql`
- 3 sample stories (HSK 1-3) with sentences and words
- Admin user: `admin@nihao.test` / password
- Test user: `test@example.com` / password

---

## 5. Storage

```bash
# Create public storage symlink (serves audio files, uploads)
php artisan storage:link
```

This creates `public/storage` → `storage/app/public`.

---

## 6. Build Frontend

```bash
npm run build
```

---

## 7. Serve the App

**With Laravel Herd (recommended for local):**
The app is automatically available at `https://nihao.test` — no extra setup.

**Without Herd:**
```bash
# All-in-one dev server (app + queue worker + logs + vite HMR)
composer run dev
```

Or manually:
```bash
php artisan serve                # App at http://localhost:8000
php artisan queue:listen         # Process background jobs (separate terminal)
npm run dev                      # Vite HMR (separate terminal)
```

---

## 8. Audio (Google Cloud TTS) — Optional

> Requires a Google Cloud account with billing enabled. Free tier: 1M characters/month.

### 8a. Setup

1. Go to https://console.cloud.google.com
2. Enable **Cloud Text-to-Speech API**
3. Create service account → download JSON key
4. Place key file (do NOT commit it):

```bash
mv ~/Downloads/your-key.json storage/google-tts-credentials.json
echo "storage/google-tts-credentials.json" >> .gitignore
```

5. Add to `.env`:

```env
GOOGLE_TTS_CREDENTIALS_PATH=/full/path/to/nihao/storage/google-tts-credentials.json
```

### 8b. Generate Audio

```bash
# Generate all words + sentences
php artisan audio:generate

# Specific story only
php artisan audio:generate --story=1

# Words only, limit to 100
php artisan audio:generate --type=words --limit=100

# Force regenerate
php artisan audio:generate --force
```

Make sure the queue worker is running to process jobs:
```bash
php artisan queue:work --stop-when-empty
```

### 8c. Verify

```bash
ls storage/app/public/audio/words/ | head
ls storage/app/public/audio/sentences/ | head
```

---

## 9. Filament Admin Panel

Access at `/admin`. Login with the admin account:

- **Email:** `admin@nihao.test`
- **Password:** `password`

Admin panel manages:
- Stories (create, edit, process sentences)
- Dictionary entries (browse, edit)
- Categories (CRUD)
- Analytics dashboard (users, reviews, retention)

---

## 10. Adding New Stories

1. Go to `/admin` → Stories → Create New
2. Fill in title (Chinese, Pinyin, Indonesian), HSK level
3. Save, then click **Process Story**
4. Paste Chinese text + Indonesian translations (one per line)
5. Audio jobs dispatch automatically (if TTS configured)

---

## 11. Running Tests

```bash
# Full suite (lint + tests)
composer test

# Just tests
php artisan test

# Specific file
php artisan test tests/Feature/SrsReviewTest.php

# Filter by name
php artisan test --filter="creates recognition card"
```

---

## 12. Code Formatting

```bash
# Auto-fix formatting
vendor/bin/pint --dirty

# Check only (CI)
vendor/bin/pint --test
```

---

## 13. Production Checklist

```bash
# .env
APP_ENV=production
APP_DEBUG=false
MAIL_MAILER=smtp            # or postmark/ses/resend
QUEUE_CONNECTION=database    # or redis

# Optimize
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan storage:link

# Queue worker (keep running via supervisor/systemd)
php artisan queue:work --daemon --tries=3
```

---

## Quick Start (copy-paste)

```bash
# From zero to running in ~2 minutes
composer install
npm install
python3 -m venv .venv && source .venv/bin/activate && pip install -r scripts/requirements.txt && deactivate
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
composer run dev
```

Open `http://localhost:8000` (or `https://nihao.test` with Herd).

---

## Key URLs

| URL | Description |
|-----|-------------|
| `/` | Homepage — story list |
| `/stories/{slug}` | Story reader |
| `/vocabulary` | Saved vocabulary |
| `/review` | SRS flashcard review |
| `/stats` | Learning statistics |
| `/admin` | Filament admin panel |

## Default Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@nihao.test` | `password` |
| User | `test@example.com` | `password` |
