# CharterDesk  
_A structured charter-party (CP) negotiation and document-generation tool for maritime fixture deals._

> Built during an internship at **Integrated Maritime Exchange (IME)**.

[![Run Locally](https://img.shields.io/badge/Run-Locally-blue)](#7-running-the-application)
[![View Recap](https://img.shields.io/badge/View-Recap-green)](#14-switching-between-html-and-pdf-recap)
[![PDF Mode](https://img.shields.io/badge/Recap-PDF--Enabled-orange)](#14-switching-between-html-and-pdf-recap)

---

## 1. Overview

CharterDesk is a PHP + MySQL application (runs on XAMPP) for structured, guided voyage
fixture negotiations between **Ship Owners**, **Charterers**, **Buyers**, and **Sellers**.
Two parties negotiate the ~38 terms of a shipping fixture in versioned offers, lock the
terms they agree on, and export a finished **Charter Party** or **recap** as HTML or PDF.

**Key Features**
- Role-based negotiation interface  
- Create/join threads with unique UUIDs  
- Firm Offer → Counter → Accept workflow  
- Collapsible ~38-term fixture form (vessel, cargo, laycan, freight, demurrage, laytime, NOR, arbitration, CP form, …)  
- Locked-field system hides accepted terms  
- Auto PDF or HTML Charter Party / recap generation (dompdf)  
- Two-party shared-thread negotiation with live polling

**Planned:** LLM-assisted clause drafting and term explanations (draft riders / force-majeure
text, explain unfamiliar CP terms, flag unusual terms).

---

## 2. Requirements

| Tool | Purpose | Command |
|------|----------|----------|
| XAMPP | PHP 8 +, Apache, MySQL | `php -v` |
| Git | Version control | `git --version` |
| Composer | PHP package manager | `composer -V` |

---

## 3. Project Structure

Place under your webroot:

```
C:\xampp\htdocs\ime-negotiation
```

```
index.html             → Chat UI & form
db_connect.php         → DB connection (PDO)
create_thread.php      → New negotiation
get_thread.php         → Fetch thread/offers
save_offer.php         → Save or counter
lock_fields.php        → Lock agreed fields
accept_offer.php       → Record acceptance
generate_recap.php     → HTML recap
generate_recap_pdf.php → PDF recap
vendor/                → Composer packages
README.md              → This file
```

---

## 4. Database Setup

Create database `ime_chat` and run:

```sql
CREATE DATABASE IF NOT EXISTS ime_negotiation;
USE ime_negotiation;

CREATE TABLE sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_uuid VARCHAR(50) UNIQUE NOT NULL,
  role VARCHAR(50),
  vessel VARCHAR(100),
  cargo VARCHAR(100),
  quantity VARCHAR(100),
  laycan VARCHAR(100),
  freight VARCHAR(100),
  riders LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_uuid VARCHAR(50) UNIQUE NOT NULL,
  created_by VARCHAR(100),
  status VARCHAR(20) DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE offers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_id INT NOT NULL,
  version INT DEFAULT 1,
  party VARCHAR(100),
  role VARCHAR(50),
  data LONGTEXT,
  riders LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES threads(id)
);

CREATE TABLE acceptances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  offer_id INT NOT NULL,
  party VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (offer_id) REFERENCES offers(id)
);
```

---

## 5. Database Connection

Connection settings live in **`db_connect.php`** and are read from environment
variables, with XAMPP-friendly defaults for local development:

| Variable | Default | Purpose |
|----------|---------|---------|
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_USER` | `root` | MySQL user |
| `DB_PASS` | _(empty)_ | MySQL password (XAMPP default is empty) |
| `DB_NAME` | `ime_negotiation` | Database name |

For local XAMPP you can leave the defaults as-is. For any deployment, set the
`DB_*` variables in your environment instead of hardcoding credentials.

Backend files include the connection with:
```php
require 'db_connect.php';   // exposes $conn (mysqli)
```

---

## 6. Install Dependencies

Dependencies are managed by Composer and are **not** committed to the repo, so
install them after cloning:

```bash
cd C:\xampp\htdocs\ime-negotiation
composer install
```
This installs everything pinned in `composer.lock` (including `dompdf/dompdf`).
If you see `missing zip extension`, enable `extension=zip` in `php.ini`.

---

## 7. Running the Application

1. Start **Apache** and **MySQL** in XAMPP.  
2. Open:  
   ```
   http://localhost/ime-negotiation/
   ```

---

## 8. Basic Usage

### Start / Join
- Enter your **name** and **role**
- Click **Start New** or **Load** (with UUID)

### Create Offer
- Type `offer` or click **Send Firm Offer**
- Fill the 40-question collapsible form  
- Submit to generate v1 offer

### Counter / Accept
- Counterparty views and counters existing offers
- Accepted terms auto-lock; only unresolved ones reappear

### Recap
- Once agreed, click **Recap**
- Opens fixture recap (HTML or PDF)

---

## 9. Chat Commands

```
start          → show quick help
offer          → open form
counter        → counter last offer
accept         → accept latest offer
recap          → open recap
load th_xxxxx  → load a thread by UUID
```

---

## 10. Git Workflow

```bash
git add -A
git commit -m "update UI, locking, recap"
git push
```
First-time setup:
```bash
git branch -M main
git remote add origin https://github.com/KanishkSigar/IME-AI-Chat-.git
git push -u origin main
```

---

## 11. Public Testing via Ngrok

```bash
ngrok http 80
```
Share:
```
https://abcd-1234.ngrok-free.app/ime-negotiation/
```
If Apache uses port 8080:
```
ngrok http 8080
```

---

## 12. Troubleshooting

| Issue | Cause | Fix |
|-------|--------|-----|
| JSON parse error | PHP emitted HTML error | Check DevTools → Network |
| View/Counter not working | Invalid JSON | Ensure `header('Content-Type: application/json')` |
| Recap missing names | Join same UUID | Reload after login |
| PDF blank | Dompdf not installed | `composer require dompdf/dompdf` |
| Thread not found | Wrong UUID | Start new negotiation |

---

## 13. Switching Between HTML and PDF Recap

### HTML Recap (default)
Frontend link:
```
generate_recap.php?uuid=<thread_uuid>
```
Opens a printable web recap.

### PDF Recap
1. Confirm Dompdf installed  
2. Change link to:
   ```
   generate_recap_pdf.php?uuid=<thread_uuid>
   ```
Downloads `fixture_recap_<uuid>.pdf` directly.  
Switch freely between the two modes—no rebuild required.

---

## 14. Production Recommendations

- Add authentication & permissions  
- Sanitize all user inputs  
- Move credentials out of webroot  
- Enable HTTPS & CSRF protection  
- Implement rate limits and audit logging

---
Developed by **IME Negotiation Automation Team**  
© 2025 IME Platform / TBI-GEU Innovation Lab
