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
- 📈 **Responsive Chart.js insights** — sales/profit trends, breakdowns and top-selling products
- 📱 **Mobile-ready UI** — adaptive dashboard cards, accessible controls and horizontally scrollable data tables

---

## ✨ Features

| Module | What it does |
|---|---|
| 🖥️ **Dashboard** | Daily KPIs, recent monthly sales/profit charts and inventory summaries |
| 🛒 **Billing** | Multi-product cart billing with automatic totals |
| 👤 **Customers** | Customer records with phone & address lookup |
| 💳 **Credits** | Outstanding dues ledger, mark-as-paid settlement |
| 🏷️ **Products** | Product catalog with price management |
| 📊 **Reports** | Accurate sales reports by date range, category, customer and payment type with CSV exports |
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
cp config/config.local.example.php config/config.local.php
#    edit config.local.php → enter your local MySQL 'user' and 'pass'

# 3. Start Apache + MySQL, then open:
#    http://localhost/BMS/setup.php   ← creates database & tables
#    http://localhost/BMS/public      ← launch the app
```

> Run the setup wizard on a local/trusted server. Do not expose the setup page to the internet.

---

## 🔒 Security Notes

- Store real database credentials in `config/config.local.php` only; this file is ignored by Git.
- **Important:** A local credentials file was previously committed to repository history. Removing it from the latest branch does not remove historical exposure. **Change that database password before using this project with real data.**
- Passwords/PINs use password hashing; authenticated pages are guarded by PHP sessions.
- This is designed for a trusted XAMPP environment, not an internet-facing production deployment without a separate security review.

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
