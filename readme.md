IME AI Negotiation Assistant

A PHP + MySQL (XAMPP) web application for structured, guided voyage fixture negotiations between shipowners, charterers, buyers, and sellers.
The system allows parties to exchange firm offers, counters, and acceptances, while automatically tracking revisions and generating fixture recaps in HTML or PDF.

1. Overview

Core features

Role-based negotiation (Owner / Charterer / Buyer / Seller)

Start or join negotiation threads using unique UUIDs

Firm offer → counter → accept workflow

40-question collapsible form covering vessel, cargo, laycan, freight, and clauses

Locked-field mechanism — accepted terms are hidden in subsequent counters

Real-time two-party sync through shared UUID

Auto-generated recap (HTML or PDF via Dompdf)

Light blue chat-based interface

2. Requirements
Tool	Purpose	Command to Verify
XAMPP	PHP 8 +, Apache, MySQL	php -v
Git	Version control	git --version
Composer	PHP dependencies	composer -V
3. Project Directory

Place the project in your XAMPP web root:

C:\xampp\htdocs\ime-negotiation

Structure
index.html             → Chat UI and form
db_connect.php         → Database connection (PDO)
create_thread.php      → Create new negotiation
get_thread.php         → Retrieve offers and locked fields
save_offer.php         → Save offers / counters
lock_fields.php        → Lock accepted terms
accept_offer.php       → Record acceptance
generate_recap.php     → HTML recap view
generate_recap_pdf.php → PDF recap (optional)
vendor/                → Composer dependencies
README.md              → This guide

4. Database Setup

Create a database (example: ime_chat) in phpMyAdmin and run:

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

5. Database Connection (db_connect.php)
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


Ensure this file is referenced in every backend script:

require 'db_connect.php';

6. Install Dompdf

From the project directory:

composer require dompdf/dompdf


If errors occur, enable extension=zip in php.ini and confirm git --version works.

7. Running the Application

Launch Apache and MySQL in the XAMPP Control Panel.

Open in a browser:
http://localhost/ime-negotiation/

8. Basic Usage

Start / Join a Thread

Enter your name and select a role.

Click Start New to create a thread or Load to join an existing UUID.

Create a Firm Offer

Type offer or click Send Firm Offer.

Complete the collapsible 40-question form.

Click Use These Terms to submit.

Counter / Accept

The counterparty views the offer from the left panel.

Click View, then Counter or Accept.

Accepted fields lock automatically.

Recap

Once all terms are accepted, click Recap.

Opens the latest fixture recap (HTML or downloadable PDF).

9. Chat Commands
start          - show quick help
offer          - open the offer form
counter        - counter the latest offer
accept         - accept latest offer
recap          - open recap view
load th_xxxxx  - load a thread by UUID

10. Git Workflow

If you manage the project through Git:

git add -A
git commit -m "Update UI, locking logic, recap generation"
git push


To link your GitHub repo:

git branch -M main
git remote add origin https://github.com/KanishkSigar/IME-AI-Chat-.git
git push -u origin main

11. Sharing Locally (Testing)

Use ngrok to generate a public URL for live testing.

ngrok http 80


Share the output URL, e.g.
https://abcd-1234.ngrok-free.app/ime-negotiation/

If Apache uses another port (e.g., 8080):

ngrok http 8080

12. Troubleshooting
Problem	Cause	Fix
JSON error in browser console	PHP outputting HTML error	Open DevTools → Network → check failing endpoint
Buttons (View / Counter) unresponsive	Invalid JSON response	Confirm scripts use header('Content-Type: application/json')
Recap missing party names	Both users must join same UUID	Re-enter names and reload
PDF not downloading	Dompdf missing	Run composer require dompdf/dompdf
“Thread not found”	UUID invalid or expired	Re-create negotiation
13. Architecture Overview
Logical Flow
 ┌─────────────┐        ┌───────────────┐
 │ Party A     │◀───▶│  PHP Backend   │◀───▶│  MySQL Database │
 └─────────────┘        └───────────────┘
         ▲                     │
         │                     ▼
 ┌─────────────┐        ┌───────────────┐
 │ Party B     │◀───▶│  Dompdf Recap │
 └─────────────┘        └───────────────┘

Mermaid (GitHub Render)
flowchart TD
    A[Party A (Owner)] <--> B((PHP Backend))
    B <--> C[(MySQL Database)]
    D[Party B (Charterer)] <--> B
    B --> E[Fixture Recap (PDF via Dompdf)]

14. Switching Between HTML and PDF Recap

You can control whether the recap opens as a web page or directly downloads as a PDF.

Option 1 — HTML Recap (default)

The frontend points to:

generate_recap.php?uuid=<thread_uuid>


This opens a clean, printable HTML recap in a new browser tab.

You can still download it manually as a PDF using Ctrl+P → Save as PDF.

Option 2 — PDF Recap (automated)

Ensure Dompdf is installed:

composer require dompdf/dompdf


Change the recap button or link to:

generate_recap_pdf.php?uuid=<thread_uuid>


This will trigger direct PDF rendering and prompt download as fixture_recap_<uuid>.pdf.

You can toggle between both views freely — no code rebuild required.

15. Production Recommendations

Before deploying beyond local testing:

Add user authentication and access control.

Validate and sanitize all form inputs.

Move credentials out of webroot.

Enable HTTPS.

Implement CSRF protection and rate limiting.

16. Credits

Developed for IME Negotiation Automation Platform
