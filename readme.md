# IME AI Negotiation Assistant  
_A lightweight AI-assisted negotiation system for maritime fixture deals_

[![Run Locally](https://img.shields.io/badge/Run-Locally-blue)](#7-running-the-application)
[![View Recap](https://img.shields.io/badge/View-Recap-green)](#14-switching-between-html-and-pdf-recap)
[![PDF Mode](https://img.shields.io/badge/Recap-PDF--Enabled-orange)](#14-switching-between-html-and-pdf-recap)

---

## 1. Overview

A PHP + MySQL application (runs on XAMPP) for structured, guided voyage fixture negotiations between **Ship Owners**, **Charterers**, **Buyers**, and **Sellers**.

**Key Features**
- Role-based negotiation interface  
- Create/join threads with unique UUIDs  
- Firm Offer → Counter → Accept workflow  
- Collapsible 40-question form for all trade terms  
- Locked-field system hides accepted terms  
- Auto PDF or HTML recap  
- Two-party live negotiation via shared thread

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

**db_connect.php**

```php
<?php
$host = '127.0.0.1';
$db   = 'ime_chat';
$user = 'root';
$pass = '';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$pdo = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
```
Make sure every backend file includes:
```php
require 'db_connect.php';
```

---

## 6. Install Dependencies

```bash
cd C:\xampp\htdocs\ime-negotiation
composer require dompdf/dompdf
```
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
