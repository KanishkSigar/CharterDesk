IME AI Negotiation Assistant — Local Setup & Usage

A complete PHP + MySQL (XAMPP) web project for guided voyage fixture negotiations between Ship Owners, Charterers, Buyers, and Sellers.
It enables parties to exchange firm offers, counters, and acceptances, track revisions, and generate automated Fixture Recaps (PDF).

✳️ Key Features

Role-based negotiation flow (Owner / Charterer / Buyer / Seller)

Start or join threads via unique UUID

Firm Offer → Counter → Accept → Auto Recap

40-question collapsible form grouped by vessel, cargo, laycan, rates, clauses & riders

Locked-field system – accepted terms hidden in later counters

Two-party sync (open same UUID in two browsers)

Dompdf for downloadable PDF recaps

Modern chat-style UI with light-blue theme

1️⃣ Prerequisites (Windows)
Tool	Purpose	Test Command
XAMPP	Apache + PHP + MySQL	php -v
Git	Version control	git --version
Composer	PHP dependencies	composer -V
2️⃣ Project Directory

Place under C:\xampp\htdocs\ime-negotiation

index.html                → chat UI (frontend)
db.php / db_connect.php    → DB connection
create_thread.php          → start thread
get_thread.php             → fetch offers
save_offer.php             → save offer/counter
lock_fields.php            → lock accepted terms
accept_offer.php           → mark offer accepted
generate_recap.php         → PDF recap
logo.png                   → header logo
vendor/                    → Composer packages
README.md                  → this guide

3️⃣ Database Setup

Create database ime_chat in phpMyAdmin, then run:

CREATE TABLE threads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_uuid VARCHAR(64) UNIQUE,
  title VARCHAR(200),
  created_by VARCHAR(100),
  locked_fields LONGTEXT DEFAULT '[]',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE offers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_uuid VARCHAR(64),
  version INT,
  party VARCHAR(100),
  role VARCHAR(100),
  data LONGTEXT,
  accepted_by VARCHAR(100),
  accepted_at DATETIME,
  INDEX(thread_uuid),
  INDEX(version)
);

CREATE TABLE field_accepts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_uuid VARCHAR(64),
  field_name VARCHAR(255),
  accepted_by VARCHAR(100),
  accepted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_field_accept (thread_uuid, field_name)
);


field_accepts enables bilateral locking — a field locks once both parties accept it.

4️⃣ Database Connection

db.php

<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'ime_chat';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
?>

5️⃣ Install Dompdf
cd C:\xampp\htdocs\ime-negotiation
composer require dompdf/dompdf


If errors → enable extension=zip in php.ini, ensure git --version works, then retry.

6️⃣ Run Server

Start Apache & MySQL in XAMPP

Visit 👉 http://localhost/ime-negotiation/

7️⃣ Demo Usage

Party A: enter name + role → Start New → copy UUID

Party B: open another browser → paste same UUID → Join

Party A: offer → fill form → Use These Terms

Party B: see offer → View / Counter / Accept

Locked terms hidden in next form; once agreed → Recap → PDF

8️⃣ Chat Commands
start | offer | counter | accept | recap | load th_xxxx

9️⃣ Git Commands
git add -A
git commit -m "UI light-blue, recap, locking, logo"
git push


If new repo:

git branch -M main
git remote add origin https://github.com/KanishkSigar/IME-AI-Chat-.git
git push -u origin main

🔗 Share Your Local App

ngrok

ngrok http 80


Share → https://xxxx-xxxx.ngrok-free.app/ime-negotiation/
If Apache on 8080 → ngrok http 8080

LAN:
Find IP (ipconfig) → http://<IP>/ime-negotiation/

🔍 Troubleshooting
Issue	Cause	Fix
JSON error	PHP echoing HTML error	check Network tab → fix PHP
View/Counter dead	invalid JSON from get_thread.php	verify headers & encoding
Recap names wrong	both must join same UUID	reload and re-enter names
PDF fail	Dompdf missing	composer require dompdf/dompdf
Offer not refreshing	stale MySQL cache	refresh browser
🧩 Architecture Overview
Text Diagram
 ┌─────────────┐        ┌───────────────┐
 │ Party A (Owner) │◀───▶│  PHP Backend   │◀───▶│  MySQL DB  │
 └─────────────┘        └───────────────┘
         ▲                     │
         │                     ▼
 ┌─────────────┐        ┌───────────────┐
 │ Party B (Charterer)│◀───▶│  Dompdf Recap │
 └─────────────┘        └───────────────┘


Flow:
1️⃣ Both users join same UUID.
2️⃣ Each offer/counter saved in DB.
3️⃣ Locked fields tracked in threads.locked_fields.
4️⃣ Recap built from latest accepted offer.

Mermaid Diagram
flowchart TD
    A[Party A (Owner)] <--> B((PHP Backend))
    B <--> C[(MySQL Database)]
    D[Party B (Charterer)] <--> B
    B --> E[Fixture Recap (PDF via Dompdf)]

✅ Production Checklist

Add auth (login / roles)

Validate input (prepare statements)

Move DB config outside webroot

HTTPS + CSRF tokens

Rate-limit and logging

📄 Credits

Prototype developed for IME Negotiation Automation Platform
