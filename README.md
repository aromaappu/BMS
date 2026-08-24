<div align="center">

# 🧾 BMS — Billing Management System

**PHP + MySQL invoicing system for small shops: billing, credits (khata), PDF receipts and daily reports**

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/Vanilla_JS-Frontend-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

*Billing · Customer Credits · Inventory · Reports · PDF Invoices*

</div>

---

## 📖 About The Project

BMS is a lightweight point-of-sale web application built for shopkeepers who need fast
billing and simple credit ("khata") tracking without heavy POS software.
It runs entirely on a classic **XAMPP stack** — PHP REST endpoints, MySQL, and a
vanilla-JS single-page UI — and installs itself with a one-click setup wizard.

### 🎯 Key Highlights

- ⚡ **One-click installer** — `setup.php` creates the database, tables and default admin in seconds
- 🔐 **Dual authentication** — username/password for admins, quick PIN login for staff counters
- 👥 **Role-based access** — separate `admin` / `staff` privileges out of the box
- 🧾 **PDF invoices** — server-side receipt generation, printable from the browser
- 💳 **Credit ledger** — track customer dues and settlements per customer
- 📡 **Clean JSON API** — every feature is an endpoint under `/api`, consumed by the SPA frontend

---

## ✨ Features

| Module | What it does |
|---|---|
| 🖥️ **Dashboard** | Daily sales summary and quick stats |
| 🛒 **Billing** | Multi-product cart billing with automatic totals |
| 👤 **Customers** | Customer records with phone & address lookup |
| 💳 **Credits** | Outstanding dues ledger, mark-as-paid settlement |
| 🏷️ **Products** | Product catalog with price management |
| 📊 **Reports** | Sales reports by date range |
| 🧾 **Invoices** | PDF bill generation (`pdf.php`) |
| 🔔 **Notifications** | Low-stock / due reminders |
| ⚙️ **Settings** | Shop profile & system configuration |

---

## 🏗️ Architecture

```
public/            SPA frontend (HTML/CSS/vanilla JS)
   │  fetch()
   ▼
api/               JSON REST endpoints (one file per module)
   │  auth.php · billing.php · customers.php · products.php
   │  credits.php · reports.php · pdf.php · notifications.php
   ▼
config/            DB connection + credentials loader
   │
   ▼
MySQL (bms_db)     users · customers · products · bills · credits …
```

```
├── api/                  # REST endpoints (auth, billing, credits, pdf…)
├── config/
│   ├── credentials.php   # safe defaults + loads local override
│   └── config.local.php  # your real credentials (git-ignored)
├── includes/             # shared helpers
├── public/               # SPA frontend (app shell, css, js)
├── setup.php             # one-click database installer
└── test_db.php           # connectivity smoke test
```

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8 (mysqli, prepared statements, sessions) |
| Database | MySQL / MariaDB (`utf8mb4`) |
| Frontend | Vanilla JavaScript SPA, HTML5, CSS3 |
| Invoices | Server-side PDF generation |
| Auth | Password + PIN hashing (`password_hash`), role-based sessions |

---

## 🚀 Getting Started

**Prerequisites:** [XAMPP](https://www.apachefriends.org/) (Apache + MySQL) or any PHP 8 + MySQL setup.

```bash
# 1. Clone into your web root
cd htdocs
git clone https://github.com/aromaappu/BMS.git
cd BMS

# 2. Add your local DB credentials (git-ignored)
cp config/credentials.php config/config.local.php
#    edit config.local.php → set 'user' and 'pass'

# 3. Start Apache + MySQL, then open:
#    http://localhost/BMS/setup.php   ← creates database & tables
#    http://localhost/BMS/public      ← launch the app
```

> `setup.php` only creates what's missing — it is safe to re-run.

---

## 🔒 Security Notes

- Real credentials live in `config/config.local.php`, which is git-ignored — the repo never contains them
- All queries use prepared statements; passwords/PINs stored as bcrypt hashes
- Session-based route guards (`requireLogin`) on every protected endpoint

---

## 📸 Screenshots

> Add screenshots here before sharing: dashboard, billing screen, credit ledger, PDF invoice.

---

## 👤 Author

**Appu Aromal** — [GitHub](https://github.com/aromaappu)

---

<div align="center">
<sub>Built with plain PHP & JavaScript — no frameworks, no build step</sub>
</div>
