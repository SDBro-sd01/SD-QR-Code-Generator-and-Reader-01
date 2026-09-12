<div align="center">

<img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
<img src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap">
<img src="https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
<img src="https://img.shields.io/badge/License-MIT-ff4d94?style=for-the-badge" alt="MIT License">

<br><br>

# 🎀 QR Studio

### A modern, dark-pink themed QR Code Generator & Reader with a fully dynamic field builder, smart auto-formatting, and MySQL-backed record management.

<br>

**Built from scratch with ❤️ using PHP · MySQL · Bootstrap · Vanilla JS**

[Features](#-features) · [Screenshots](#-screenshots) · [Installation](#-installation) · [Database](#-database-setup) · [Usage](#-usage) · [API](#-api-reference)

</div>

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Screenshots](#-screenshots)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Requirements](#-requirements)
- [Installation](#-installation)
  - [1. Clone the repository](#1-clone-the-repository)
  - [2. Install Composer dependencies](#2-install-composer-dependencies)
  - [3. Configure environment variables](#3-configure-environment-variables)
  - [4. Set up the database](#4-set-up-the-database)
  - [5. Create the record directory](#5-create-the-record-directory)
  - [6. Run the app](#6-run-the-app)
- [Database Setup](#-database-setup)
- [Usage](#-usage)
  - [Generating a QR Code](#generating-a-qr-code)
  - [Reading a QR Code](#reading-a-qr-code)
  - [Field Builder](#field-builder)
  - [Drag & Drop Sorting](#drag--drop-sorting)
  - [Locked / Auto-Format Fields](#locked--auto-format-fields)
- [Token Reference](#-token-reference)
- [API Reference](#-api-reference)
- [SQL Queries Reference](#-sql-queries-reference)
- [Configuration](#-configuration)
- [Security Notes](#-security-notes)
- [Contributing](#-contributing)
- [License](#-license)
- [Author](#-author)
- [Acknowledgements](#-acknowledgements)

---

## 🌟 Overview

**QR Studio** is a fully self-hosted web application that lets you **generate**, **download**, **store**, and **read** QR codes — all inside a gorgeous dark-pink themed UI built on top of Bootstrap 5.

Unlike generic QR generators, QR Studio comes with a **dynamic field builder** that allows you to define your own input fields on the fly (text, textarea, number, email, telephone, date, select dropdown, and *smart locked fields*). Every record you generate is saved to a MySQL database and its QR image is written to a local `/record/` folder, named after the person's full name.

The **Locked / Auto-Format** field type is the star of the show: it supports pattern-based value generation using tokens like `{YYYY}`, `{#####}`, and — most importantly — **references to other fields** (`{@birthday:year}`, `{@full_name:initials}`, …). This means you can automatically produce codes like:

```
EMP-1995-00007
STU/2024/03/0012
KP-2609-007
```

without writing a single line of PHP for each pattern.

---

## ✨ Features

### 🎨 UI / UX
- **Modern dark-pink theme** — custom glassmorphism cards, gradient accents, ambient radial glows
- **Fully responsive** — works beautifully on mobile, tablet, and desktop
- **Bootstrap 5.3** + Bootstrap Icons + Google Fonts (`Outfit`)
- **Pill-style tabs**, animated transitions, and custom scrollbars
- **Toast-like inline alerts** with icons

### 🔧 QR Generator
- Live QR preview as you type (via `qrcodejs`)
- High error-correction level (**H**) for maximum scannability
- **Downloadable PNG** — exports with a clean white padding around the code
- **Save to MySQL** + **save PNG to `/record/`** in one click
- Auto-named PNG files based on the Full Name field (with collision-safe suffixes)

### 🔍 QR Reader
- **Drag & drop** or **file picker** upload
- Automatic scaling for large images (up to 1500px)
- Powered by `jsQR` with dual inversion attempts for low-contrast images
- Smart JSON detection — if the QR contains JSON, fields are shown in a nice structured view

### 🧱 Dynamic Field Builder
- Add / edit / delete fields — no code changes required
- **8 field types**: `text`, `textarea`, `number`, `email`, `tel`, `date`, `select`, `locked`
- **Drag & drop sorting** with auto-save
- **Select** fields accept comma- or newline-separated options
- **Required / optional** toggle per field

### 🔒 Locked / Auto-Format Fields
- Pattern-based value generation using tokens
- **Interactive token palette** — click a chip to insert it at the cursor position
- Supports:
  - Current date/time tokens (`{YYYY}`, `{MM}`, `{DD}`, …)
  - Zero-padded counters (`{#####}`, `{###}`, `{#}`)
  - **References to other fields** (`{@field_key}`) with transformations (`{@birthday:year}`, `{@full_name:initials}`, …)
- Real-time preview in the generator *before* saving (predicts the next record ID)

### 🗄️ Record Management
- Every record stored in MySQL with a JSON payload of all field values
- **Recent Records** grid with thumbnail, name, ID, and timestamp
- **View modal** — displays all field values + the stored QR image
- **Delete** removes both the DB row and the PNG file from `/record/`

### 🔐 Security
- All SQL queries use **prepared statements** (PDO)
- Output is HTML-escaped on the client
- Environment variables kept out of version control (`.env` + `vendor/` ignored)

---

## 📸 Screenshots

> 💡 *Add your own screenshots to the `docs/screenshots/` folder and update the paths below.*

| Generator | Field Builder |
|---|---|
| ![Generator](docs/screenshots/generator.png) | ![Field Builder](docs/screenshots/builder.png) |

| QR Reader | Record View |
|---|---|
| ![Reader](docs/screenshots/reader.png) | ![View Modal](docs/screenshots/view.png) |

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.0+ (PDO, MySQL) |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript (ES6) |
| **UI Framework** | Bootstrap 5.3.3 |
| **Icons & Fonts** | Bootstrap Icons 1.11, Google Fonts (Outfit) |
| **QR Libraries** | [qrcodejs](https://github.com/davidshimjs/qrcodejs) (generate), [jsQR](https://github.com/cozmo/jsQR) (read) |
| **Env Management** | [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) |
| **Package Manager** | Composer |

---

## 📁 Project Structure

```
qr-studio/
├── assets/
│   ├── css/
│   │   └── style.css           # Dark pink theme
│   └── js/
│       └── app.js              # Client logic (generator, reader, builder)
├── docs/
│   └── screenshots/            # README screenshots
├── includes/
│   ├── db_connection.php       # PDO + dotenv bootstrap
│   └── helpers.php             # Shared helpers (token engine, slug, schema)
├── record/                     # (gitignored) saved QR PNGs
├── vendor/                     # (gitignored) Composer packages
├── .env                        # (gitignored) local secrets
├── .env.example                # template for .env
├── .gitignore
├── api.php                     # Single JSON API endpoint
├── composer.json
├── index.php                   # Main UI
├── schema.sql                  # DB schema + seed data
└── README.md
```

---

## ✅ Requirements

- **PHP** ≥ 8.0 with extensions: `pdo`, `pdo_mysql`, `mbstring`, `json`
- **MySQL** ≥ 5.7 or **MariaDB** ≥ 10.3
- **Composer** ≥ 2.0
- **Web server** — Apache, Nginx, or PHP built-in server
- **Write permissions** on the `/record/` directory

---

## 🚀 Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/qr-studio.git
cd qr-studio
```

### 2. Install Composer dependencies

```bash
composer install
```

This installs `vlucas/phpdotenv` which is used to load environment variables from `.env`.

### 3. Configure environment variables

Copy the example file and fill in your database credentials:

```bash
cp .env.example .env
```

Then edit `.env`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=MYQRCODEBASE_01
DB_USER=root
DB_PASS=your_secure_password_here
DB_CHARSET=utf8mb4
APP_TIMEZONE=Asia/Colombo
```

> ⚠️ **Never commit `.env` to version control.** It's already listed in `.gitignore`.

### 4. Set up the database

**Option A — Automatic (recommended):**

Just open the app in your browser. `ensure_schema()` in `includes/helpers.php` will create the database tables and seed the default fields on first run.

**Option B — Manual:**

```bash
mysql -u root -p < schema.sql
```

Or run the SQL from the [Database Setup](#-database-setup) section manually in phpMyAdmin.

### 5. Create the record directory

```bash
mkdir record
chmod 0775 record
```

On Windows, no `chmod` is needed — just make sure the folder exists and is writable.

### 6. Run the app

**Using PHP built-in server (for development):**

```bash
php -S localhost:8000
```

Then open **http://localhost:8000** in your browser.

**Using Apache / Nginx:**

Point your virtual host's document root to the project folder. Make sure `mod_rewrite` isn't needed (it isn't — we use plain PHP files).

---

## 🗄️ Database Setup

### Full Schema (`schema.sql`)

```sql
-- =====================================================================
--  QR Studio — MySQL Schema
--  Database: MYQRCODEBASE_01
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';

CREATE DATABASE IF NOT EXISTS `MYQRCODEBASE_01`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `MYQRCODEBASE_01`;

-- ---------------------------------------------------------------------
--  FIELD DEFINITIONS
--  Stores every custom input field created via the Field Builder.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_field_definitions` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `field_key`     VARCHAR(64)  NOT NULL UNIQUE,
  `label`         VARCHAR(150) NOT NULL,
  `field_type`    ENUM('text','textarea','number','email','tel','date','select','locked')
                  NOT NULL DEFAULT 'text',
  `placeholder`   VARCHAR(200) DEFAULT NULL,
  `options_json`  TEXT         DEFAULT NULL,
  `auto_format`   VARCHAR(200) DEFAULT NULL,
  `is_required`   TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`    INT          NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  RECORDS
--  Every generated QR record. `record_data` holds a JSON map of
--  {label: value} used as the QR payload.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_records` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `record_code`   VARCHAR(120) DEFAULT NULL,
  `full_name`     VARCHAR(220) NOT NULL,
  `record_data`   LONGTEXT     NOT NULL,
  `qr_image`      VARCHAR(255) DEFAULT NULL,
  `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_full_name` (`full_name`),
  INDEX `idx_created`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  SEED — default fields
-- ---------------------------------------------------------------------
INSERT INTO `qr_field_definitions`
  (`field_key`, `label`, `field_type`, `placeholder`, `is_required`, `sort_order`)
VALUES
  ('full_name',          'Full Name',           'text',     'e.g. Kasun Perera Silva', 1, 1),
  ('name_with_initials', 'Name with Initials', 'text',     'e.g. K. P. Silva',        1, 2),
  ('age',                'Age',                 'number',   'e.g. 25',                 1, 3),
  ('address',            'Address',             'textarea', 'e.g. No. 24, Colombo 05', 1, 4)
ON DUPLICATE KEY UPDATE `field_key` = `field_key`;
```

### Table: `qr_field_definitions`

| Column | Type | Description |
|---|---|---|
| `id` | INT (PK) | Auto-increment identifier |
| `field_key` | VARCHAR(64) UNIQUE | Machine-safe key (e.g. `full_name`) |
| `label` | VARCHAR(150) | Human-readable label shown in the UI |
| `field_type` | ENUM | `text` / `textarea` / `number` / `email` / `tel` / `date` / `select` / `locked` |
| `placeholder` | VARCHAR(200) | Input placeholder text |
| `options_json` | TEXT | JSON array of options (for `select` type) |
| `auto_format` | VARCHAR(200) | Pattern string (for `locked` type) |
| `is_required` | TINYINT(1) | 1 = required, 0 = optional |
| `sort_order` | INT | Order in the form (drag & drop) |
| `is_active` | TINYINT(1) | Soft-delete flag |
| `created_at` / `updated_at` | TIMESTAMP | Audit fields |

### Table: `qr_records`

| Column | Type | Description |
|---|---|---|
| `id` | INT (PK) | Auto-increment identifier (also used as the `{#####}` counter source) |
| `record_code` | VARCHAR(120) | Value of the first locked field (if any) |
| `full_name` | VARCHAR(220) | Extracted Full Name — used as the PNG filename |
| `record_data` | LONGTEXT | JSON `{label: value}` payload |
| `qr_image` | VARCHAR(255) | Relative path to the PNG (e.g. `record/John_Doe.png`) |
| `created_at` / `updated_at` | TIMESTAMP | Audit fields |

---

## 🧑‍💻 Usage

### Generating a QR Code

1. Open **QR Studio** in your browser.
2. On the **Generator** tab, fill in the dynamic form.
3. Click **Generate QR Code** — the preview panel updates instantly.
4. Click **Download PNG** to save the image locally, *or*
5. Click **Save to MySQL & /record** to store the record and PNG.

### Reading a QR Code

1. Switch to the **Reader** tab.
2. Drag an image into the drop zone (or click to browse).
3. The decoded content appears instantly. If it's JSON, fields are structured.

### Field Builder

1. Go to the **Field Builder** tab.
2. Click **New Field**, fill in the form, choose a type, and save.
3. Fields immediately appear in the Generator form.

### Drag & Drop Sorting

- Grab the `⋮⋮` grip handle on the left of any field row.
- Drag it up or down — the order is saved automatically via AJAX.

### Locked / Auto-Format Fields

1. In the Field Builder, choose type **Locked / Auto-Format**.
2. Use the **token palette** below the input to build your pattern:
   - **Date / Time chips** → `{YYYY}`, `{MM}`, `{DD}`, …
   - **Counter chips** → `{#}`, `{###}`, `{#####}`
   - **Reference another field** → pick a field and a part (e.g. `birthday` → `year`)
3. Click a chip to insert it at the cursor position in the Auto-Format input.
4. Save the field.

**Example:** With a `Birthday` date field, create a locked field with format:

```
EMP-{@birthday:year}-{#####}
```

A record with birthday `1995-03-04` and record ID `7` produces:

```
EMP-1995-00007
```

---

## 🪙 Token Reference

| Token | Output | Example |
|---|---|---|
| `{YYYY}` | 4-digit year (current) | `2026` |
| `{YY}` | 2-digit year (current) | `26` |
| `{MM}` | Month, zero-padded | `09` |
| `{DD}` | Day, zero-padded | `12` |
| `{HH}` | Hour (24h) | `14` |
| `{MI}` | Minute | `35` |
| `{SS}` | Second | `07` |
| `{#}` | Record ID (raw) | `7` |
| `{###}` | Record ID, 3-padded | `007` |
| `{#####}` | Record ID, 5-padded | `00007` |
| `{########}` | Record ID, 8-padded | `00000007` |
| `{@field_key}` | Raw value of another field | `1995-03-04` |
| `{@field_key:year}` | Extracted year | `1995` |
| `{@field_key:yy}` | 2-digit year | `95` |
| `{@field_key:month}` / `:mm` | Month (2-digit) | `03` |
| `{@field_key:day}` / `:dd` | Day (2-digit) | `04` |
| `{@field_key:upper}` | UPPERCASE | `KASUN` |
| `{@field_key:lower}` | lowercase | `kasun` |
| `{@field_key:first}` | First character | `K` |
| `{@field_key:last}` | Last character | `n` |
| `{@field_key:initials}` | Initials of all words | `KP` |
| `{@field_key:length}` | Length in characters | `5` |
| `{@field_key:slug}` | Filename-safe slug | `Kasun_Perera` |

---

## 🔌 API Reference

All API actions are handled through a single endpoint: **`api.php`**.

The action is passed either as `?action=...` (GET) or via `POST` form-data / `FormData`.

### Field Definitions

| Action | Method | Payload | Description |
|---|---|---|---|
| `list_fields` | GET | — | List all active fields |
| `save_field` | POST | `id`, `label`, `field_key`, `field_type`, `placeholder`, `options`, `auto_format`, `is_required`, `sort_order` | Create or update a field |
| `delete_field` | POST | `id` | Delete a field |
| `reorder_fields` | POST | `order` (JSON array of IDs) | Persist drag & drop order |

### Records

| Action | Method | Payload | Description |
|---|---|---|---|
| `list_records` | GET | — | List the last 100 records |
| `get_record` | GET | `id` | Get a single record (with decoded JSON) |
| `save_record` | POST | `field_key=value` for each field | Validate + insert a record |
| `peek_next_id` | POST | `values` (JSON of current form values) | Preview locked-field values before saving |
| `upload_qr` | POST | `record_id`, `image` (base64 data URL) | Save the QR PNG to `/record/` |
| `delete_record` | POST | `id` | Delete a record **and** its PNG file |

### Example Requests

**Save a record:**

```bash
curl -X POST http://localhost:8000/api.php \
  -F "action=save_record" \
  -F "full_name=John Doe" \
  -F "name_with_initials=J. Doe" \
  -F "age=29" \
  -F "address=123 Main St, Colombo"
```

**List records:**

```bash
curl "http://localhost:8000/api.php?action=list_records"
```

**Response (success):**

```json
{
  "ok": true,
  "record": {
    "id": 7,
    "record_code": "EMP-1995-00007",
    "full_name": "John Doe",
    "created_at": "2026-09-12 14:35:07"
  },
  "payload": {
    "Full Name": "John Doe",
    "Name with Initials": "J. Doe",
    "Age": "29",
    "Address": "123 Main St, Colombo",
    "Employee Code": "EMP-1995-00007"
  }
}
```

---

## 🧮 SQL Queries Reference

A quick reference of every query the app runs.

### Field Definitions

**Insert a new field:**

```sql
INSERT INTO qr_field_definitions
  (field_key, label, field_type, placeholder, options_json, auto_format, is_required, sort_order)
VALUES
  (:key, :label, :type, :ph, :options, :auto, :req, :sort);
```

**Update an existing field:**

```sql
UPDATE qr_field_definitions SET
  field_key=:k, label=:l, field_type=:t, placeholder=:p,
  options_json=:o, auto_format=:a, is_required=:r, sort_order=:s
WHERE id=:id;
```

**List active fields (ordered):**

```sql
SELECT * FROM qr_field_definitions
WHERE is_active = 1
ORDER BY sort_order ASC, id ASC;
```

**Delete a field:**

```sql
DELETE FROM qr_field_definitions WHERE id = :id;
```

**Reorder (drag & drop):**

```sql
UPDATE qr_field_definitions SET sort_order = :order WHERE id = :id;
```

### Records

**Insert (with placeholder for locked fields):**

```sql
INSERT INTO qr_records (record_code, full_name, record_data)
VALUES (:code, :name, :json);
```

**Update with final locked values:**

```sql
UPDATE qr_records
SET record_code = :code, record_data = :json
WHERE id = :id;
```

**Attach QR image path:**

```sql
UPDATE qr_records SET qr_image = :path WHERE id = :id;
```

**Get next record ID (for preview):**

```sql
SELECT COALESCE(MAX(id), 0) + 1 FROM qr_records;
```

**List recent records:**

```sql
SELECT id, record_code, full_name, qr_image, created_at
FROM qr_records
ORDER BY id DESC
LIMIT 100;
```

**Get a single record:**

```sql
SELECT * FROM qr_records WHERE id = :id;
```

**Delete a record:**

```sql
DELETE FROM qr_records WHERE id = :id;
```

**Search by name (optional):**

```sql
SELECT id, full_name, record_code, created_at
FROM qr_records
WHERE full_name LIKE CONCAT('%', :q, '%')
ORDER BY id DESC
LIMIT 50;
```

**Cleanup old records (optional):**

```sql
DELETE FROM qr_records
WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY);
```

---

## ⚙️ Configuration

### Environment Variables (`.env`)

| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `localhost` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_NAME` | `MYQRCODEBASE_01` | Database name |
| `DB_USER` | `root` | Database user |
| `DB_PASS` | — | Database password |
| `DB_CHARSET` | `utf8mb4` | Connection charset |
| `APP_TIMEZONE` | `Asia/Colombo` | PHP timezone for `{YYYY}` etc. |

### `includes/db_connection.php`

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

/* ---------- Load .env ---------- */
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/* ---------- Validate required vars ---------- */
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

$host    = $_ENV['DB_HOST'];
$port    = $_ENV['DB_PORT'] ?? '3306';
$dbname  = $_ENV['DB_NAME'];
$username= $_ENV['DB_USER'];
$password= $_ENV['DB_PASS'];
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$tz      = $_ENV['APP_TIMEZONE'] ?? 'Asia/Colombo';

/* ---------- Connect ---------- */
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    $pdo->exec("SET time_zone = '" . (new DateTime('now', new DateTimeZone($tz)))->format('P') . "'");
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Please try again later.');
}
```

### `.env.example`

```env
# ---------------------------------------------------------------
#  QR Studio — Environment Configuration
#  Copy this file to `.env` and fill in your own values.
#  NEVER commit the real `.env` file.
# ---------------------------------------------------------------

# MySQL
DB_HOST=localhost
DB_PORT=3306
DB_NAME=MYQRCODEBASE_01
DB_USER=root
DB_PASS=change_me

# Connection
DB_CHARSET=utf8mb4

# App
APP_TIMEZONE=Asia/Colombo
```

### `.gitignore`

```gitignore
# ---------- Dependencies ----------
/vendor/
/node_modules/

# ---------- Environment ----------
.env
.env.local
.env.*.local

# ---------- Generated files ----------
/record/
!/record/.gitkeep

# ---------- Editor / OS ----------
.idea/
.vscode/
*.swp
.DS_Store
Thumbs.db

# ---------- Logs ----------
*.log
logs/

# ---------- Composer ----------
composer.lock
```

> 💡 Add a small `record/.gitkeep` file so the folder still exists after a fresh clone:
>
> ```bash
> touch record/.gitkeep
> ```

### `composer.json`

```json
{
  "name": "your-username/qr-studio",
  "description": "Modern dark-pink QR code generator & reader with a dynamic field builder and MySQL storage.",
  "type": "project",
  "license": "MIT",
  "require": {
    "php": ">=8.0",
    "ext-pdo": "*",
    "ext-mbstring": "*",
    "vlucas/phpdotenv": "^5.6"
  },
  "autoload": {
    "files": [
      "includes/helpers.php"
    ]
  },
  "config": {
    "sort-packages": true,
    "optimize-autoloader": true
  }
}
```

---

## 🔒 Security Notes

- **Never commit `.env`** — the file is in `.gitignore` for a reason.
- **Never commit `vendor/`** — run `composer install` on the target machine.
- **Never commit `record/`** — generated QR PNGs may contain personal data.
- All database queries use **PDO prepared statements**.
- All user-generated output is **HTML-escaped** on the client.
- Ensure `/record/` has the correct **write permissions** (`0775` on Linux/macOS).
- If you deploy to production, set `display_errors=Off` in `php.ini` and log errors instead.

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

1. Fork the project
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add some amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

---

## 📄 License

Distributed under the **MIT License**. See `LICENSE` for more information.

---

## 👤 Author

**Your Name**

- GitHub: [@SDBro-sd01](https://github.com/SDBro-sd01)

> ⭐ If you find this project useful, please consider giving it a star on GitHub!

---

## 🙏 Acknowledgements

- [Bootstrap 5](https://getbootstrap.com/) — UI foundation
- [Bootstrap Icons](https://icons.getbootstrap.com/) — iconography
- [qrcodejs](https://github.com/davidshimjs/qrcodejs) — QR generation
- [jsQR](https://github.com/cozmo/jsQR) — QR decoding
- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) — environment management
- [Google Fonts — Outfit](https://fonts.google.com/specimen/Outfit) — typography

<div align="center">

**Made with ❤️ and a lot of dark pink.**

</div>
