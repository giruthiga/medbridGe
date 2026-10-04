# medbridGe

> Hospital data readiness scanner for HIS migration — built with Laravel.

medbridGe scans messy hospital CSV files, cleans patient data, scores readiness for migration to a Hospital Information System (HIS), and exports standardized files ready for import.

Built as a **pre-sales tool** for HIS vendors to reduce data migration friction and shorten sales cycles.

---

## Features

- **CSV Upload** — drag-and-drop interface with file validation
- **Automatic Parsing** — every row stored in database, malformed rows flagged
- **Column Mapping** — auto-suggestions plus manual override with confidence badges
- **Data Cleaning** — normalizes phone numbers, dates, gender, names, cities, blood types, and national IDs
- **Change Tracking** — every fix logged with before/after values and reason
- **Readiness Scoring** — weighted score (0-100) with explainable breakdown
- **Gap Analysis** — maps data fields to 8 standard HIS modules
- **Multi-Format Export** — CSV, JSON, HIS-ready CSV, and text report
- **Dashboard and Insights** — cross-upload analytics and trends
- **Activity Log** — full audit trail of all actions

---

## The 5-Stage Pipeline

```
Upload  ->  Parse  ->  Match  ->  Clean  ->  Score  ->  Export
```

| Stage | What Happens |
|-------|--------------|
| Upload | Accept CSV file via drag-and-drop |
| Parse | Read every row, store in database, flag malformed rows |
| Match | Map hospital columns to standard HIS schema |
| Clean | Normalize phones, dates, gender, names, cities |
| Score | Calculate readiness percentage with breakdown |
| Export | Generate clean CSV, JSON, or HIS-ready files |

---

## Readiness Scoring

The score is a weighted average of 4 factors:

| Factor | Weight | Measures |
|--------|--------|----------|
| Column Coverage | 30% | Standard fields matched |
| Row Validity | 40% | Rows without issues |
| Data Quality | 20% | Data already clean |
| Required Fields | 10% | Essential fields present |

**Score Bands:**

- Excellent (90-100) — Ready for production
- Good (75-89) — Ready with minor fixes
- Fair (50-74) — Manual review needed
- Poor (25-49) — Significant work needed
- Critical (0-24) — Not ready

---

## Tech Stack

- **Backend** — Laravel 11, PHP 8.3
- **Database** — SQLite (local) / MySQL-ready
- **Frontend** — Blade, Tailwind CSS
- **Authentication** — Laravel Breeze
- **Build Tool** — Vite
- **Dev Environment** — Laragon, VS Code

---

## Installation

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js and npm
- Git

### Setup

```bash
# Clone the repository
git clone https://github.com/giruthiga/medbridge.git
cd medbridge

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Install frontend dependencies
npm install

# Build assets
npm run build

# Start the server
php artisan serve
```

Visit `http://127.0.0.1:8000` in your browser.

---

## Usage

1. **Register** an account
2. **Upload** a hospital CSV file
3. **Match** your columns to the standard HIS schema
4. **Clean** the data
5. **Score** readiness
6. **Export** the cleaned file in your preferred format

---

## Project Structure

```
medbridge/
├── app/
│   ├── Http/Controllers/     # Route handlers
│   ├── Models/               # Eloquent models
│   └── Services/             # Business logic
│       ├── CsvParser.php
│       ├── Cleaner.php
│       ├── ReadinessScorer.php
│       ├── GapAnalyzer.php
│       └── DataExporter.php
├── database/
│   ├── migrations/           # Schema definitions
│   └── database.sqlite       # Local database
├── resources/views/          # Blade templates
├── routes/web.php            # URL routing
└── public/                   # Web root
```

---

## Testing

Tested with a 50-row Bahrain hospital dataset containing real-world data quality issues:

- 15+ date formats
- 6+ phone formats
- 8+ gender variants
- Empty names and malformed rows

**Results:**

| Metric | Result |
|--------|--------|
| Rows parsed | 50 / 50 |
| Dates standardized | 100% |
| Phones normalized | 95%+ |
| Gender standardized | 90%+ |
| Malformed rows detected | 2 / 50 |
| Export alignment | 8 / 8 columns |

---

## Current Status

- Local development — Complete
- Public deployment — Not yet deployed
- Currently runs on `localhost` for demonstration and testing

---

## Future Scope

1. Deploy to production hosting
2. Excel and XLSX file support
3. Background queue processing for large files
4. Duplicate patient detection with fuzzy matching
5. AI-powered column auto-mapping
6. Multi-country phone logic (GCC, MENA)
7. REST API for HIS integration
8. Cloud storage (S3)

---

## Limitations

- CSV files only (no Excel yet)
- 10 MB file size limit
- Bahrain-specific phone logic (+973 only)
- Single file processing

---

## License

This project is open source and available for educational and portfolio purposes.

---

## Author

**Giruthiga**
- GitHub: [@giruthiga](https://github.com/giruthiga)
- Email: giruthi97@gmail.com

---

Built to solve a real business problem — hospital data migration friction.
```

---

## Steps to Add It

1. **In VS Code** — right-click the project root (where `artisan` lives) → **New File** → name it `README.md`
2. **Paste** the content above
3. **Save** (Ctrl+S)
4. **In terminal:**

```bash
git add README.md
git commit -m "Add README"
git push
```

---

