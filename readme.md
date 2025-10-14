# IME AI Negotiation Assistant — Local Setup & Usage

A lightweight PHP + MySQL project (runs on XAMPP) for guided voyage fixture negotiations between Ship Owners, Charterers, Buyers, and Sellers. Features:

- Start/join negotiation threads (UUID)
- Firm offer → counters → accept → auto PDF recap (Dompdf)
- 40-question structured offer form (collapsible sections)
- Agreed-terms panel (only unresolved terms appear in the next offer)

---

## 1) Prerequisites (Windows)
- XAMPP (Apache + PHP 8.x + MySQL)
- Git
- Composer

Verify in Command Prompt:
```bash
php -v
mysql --version
git --version
composer -V
```

---

## 2) Project Location
Place the project under XAMPP webroot:
```
C:\xampp\htdocs\ime-negotiation
```
Key files/folders:
```
index.html
create_thread.php
get_thread.php
save_offer.php
accept_offer.php
generate_recap.php
db_connect.php
style.css
composer.json
composer.lock
vendor/    (created by Composer)
```

---

## 3) Database Setup
Open **phpMyAdmin** → create database, e.g. `ime_chat` → run schema:

```sql
CREATE TABLE IF NOT EXISTS threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_uuid VARCHAR(60) UNIQUE,
  title VARCHAR(255) DEFAULT '',
  created_by VARCHAR(100) DEFAULT '',
  status ENUM('open','countered','agreed','cancelled') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS offers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_id INT,
  version INT DEFAULT 1,
  party VARCHAR(100),
  role VARCHAR(30),
  data LONGTEXT,
  riders TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS acceptances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  offer_id INT,
  party VARCHAR(100),
  accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
);
```

**Hardening & Indexes (optional but recommended)**
```sql
-- Charset/engine
ALTER TABLE threads  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE offers   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE acceptances CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- NOT NULLs
ALTER TABLE threads 
  MODIFY thread_uuid VARCHAR(60) NOT NULL,
  MODIFY title VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY created_by VARCHAR(100) NOT NULL DEFAULT '',
  MODIFY status ENUM('open','countered','agreed','cancelled') NOT NULL DEFAULT 'open';

ALTER TABLE offers
  MODIFY thread_id INT NOT NULL,
  MODIFY version INT NOT NULL,
  MODIFY party VARCHAR(100) NOT NULL,
  MODIFY role VARCHAR(30) NOT NULL,
  MODIFY riders TEXT NULL;

ALTER TABLE acceptances
  MODIFY offer_id INT NOT NULL,
  MODIFY party VARCHAR(100) NOT NULL;

-- Uniqueness & indexes
ALTER TABLE threads
  ADD UNIQUE KEY uq_threads_uuid (thread_uuid);

ALTER TABLE offers
  ADD UNIQUE KEY uq_offers_thread_version (thread_id, version),
  ADD INDEX ix_offers_thread_created (thread_id, created_at),
  ADD INDEX ix_offers_thread_version (thread_id, version);

-- Clean duplicates before unique acceptances key
UPDATE acceptances SET party = TRIM(party);
DELETE a
FROM acceptances a
JOIN (
  SELECT offer_id, party, MIN(id) AS keep_id
  FROM acceptances
  GROUP BY offer_id, party
  HAVING COUNT(*) > 1
) d
  ON a.offer_id = d.offer_id
 AND a.party    = d.party
WHERE a.id <> d.keep_id;

ALTER TABLE acceptances
  ADD UNIQUE KEY uq_accept_once (offer_id, party),
  ADD INDEX ix_accept_offer (offer_id);
```

---

## 4) Configure DB Connection
Create `db_connect.php`:
```php
<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'ime_chat';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('Connection failed: '.$conn->connect_error);
if (method_exists($conn, 'set_charset')) $conn->set_charset('utf8mb4');
```

---

## 5) Install Dompdf
```bash
cd C:\xampp\htdocs\ime-negotiation
composer require dompdf/dompdf
```
If Composer complains about `zip` or `git`, enable `extension=zip` in `C:\xampp\php\php.ini` and ensure `git --version` works, then re-run.

---

## 6) Start Local Server
Open **XAMPP Control Panel** → Start **Apache** and **MySQL**.
- App: http://localhost/ime-negotiation/
- PDF test (optional): http://localhost/ime-negotiation/test_dompdf.php

---

## 7) Using the App (Two-Party Demo)
1) Enter your **name** and choose **role** → click **start new** → copy **UUID**.
2) Click **Send Firm Offer** → fill required terms → **Use These Terms** (creates v1).
3) Open a second tab/window → paste same **UUID** → **join**.
4) In **offers (history)**: **view** (see JSON), **counter** (prefilled form → submit), **accept**.
5) Once accepted → **Generate Recap** (PDF opens/new tab).

**Tip:** The left panel shows **Agreed Terms** (auto from last cross-party match). Only unresolved fields appear in the next offer form.

**Chat commands** (bottom input):
```
start | offer | counter v2 | accept | recap | load th_XXXX
```

---

## 8) Git (optional)
```bash
cd C:\xampp\htdocs\ime-negotiation
git init
git add .
git commit -m "initial commit"

# if your GitHub repo default branch is main
git branch -M main
git remote add origin https://github.com/<you>/<repo>
git push -u origin main

# stop tracking vendor
echo /vendor > .gitignore
git rm -r --cached vendor
git commit -m "stop tracking vendor"
git push
```

---

## 9) Troubleshooting
- **Unexpected token '<' ... not valid JSON** → A PHP script emitted HTML errors. Open DevTools → Network → see which endpoint failed → fix PHP errors.
- **offer not found on view/counter** → Ensure `get_thread.php` returns numeric `id`/`version` (cast in PHP) and frontend casts with `Number()`.
- **Duplicate unique key error** → Drop or de-dup before re-adding the index (see schema section).
- **Dompdf missing fonts/zip** → Enable `extension=zip` in `php.ini`; re-run Composer.

---

## 10) What’s Inside
- Collapsible **40-question** form grouped into sections
- Only unresolved terms are included in next firm offer
- Versioned offers with view/counter/accept
- One-click recap generation (PDF)

> For production: add auth, permissions, input validation (prepared statements), and move secrets out of webroot.
