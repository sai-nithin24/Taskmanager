# TaskBoard — Agile Task Manager
> PHP · MySQL · HTML · CSS · JavaScript  
> Sprint-ready, feature-first folder structure

---

## Project Structure

```
taskboard/
│
├── config/
│   ├── database.php          ← DB credentials (copy & edit locally)
│   └── schema.sql            ← MySQL schema + seed data
│
├── src/                      ← Back-end (PHP)
│   ├── api/
│   │   └── tasks.php         ← REST API controller (GET/POST/PATCH/DELETE)
│   ├── models/
│   │   ├── Database.php      ← PDO singleton
│   │   └── TaskModel.php     ← Data-access layer (all SQL here)
│   └── utils/
│       ├── Validator.php     ← Reusable server-side validation
│       └── Response.php      ← JSON response helper
│
├── public/                   ← Front-end (document root)
│   ├── index.html            ← Single-page UI
│   └── assets/
│       ├── css/
│       │   └── styles.css    ← Design tokens + all component styles
│       └── js/
│           └── app.js        ← IIFE modules: Api, Validate, Toast, Tasks, Form, App
│
├── tests/
│   └── TaskModelTest.php     ← Unit tests (no PHPUnit required)
│
└── README.md
```

---

## Quick Start

### 1 — Database
```sql
-- In MySQL client or phpMyAdmin:
SOURCE config/schema.sql;
```

### 2 — Config
Edit `config/database.php` with your credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'taskboard');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

### 3 — Serve
```bash
# Option A: PHP built-in server (development)
php -S localhost:8000 -t public/

# Option B: Apache/Nginx — point document root to public/
# Option C: XAMPP/WAMP — place project in htdocs/
```

Open `http://localhost:8000` in your browser.

### 4 — Run tests
```bash
php tests/TaskModelTest.php
```

---

## API Reference

Base URL: `/src/api/tasks.php`

| Method | Params / Body | Description |
|--------|--------------|-------------|
| `GET`  | `?filter=all\|pending\|completed` | List tasks |
| `GET`  | `?stats=1` | Aggregated counts + % |
| `POST` | `{ title, priority }` | Create a task |
| `PATCH`| `{ id, status }` | Update task status |
| `DELETE`| `{ id }` | Remove a task |

All responses:
```json
{ "success": true, "message": "...", "data": {...} }
{ "success": false, "message": "...", "errors": {} }
```

---

## Validation Rules

| Rule | Detail |
|------|--------|
| Required | Title must not be empty |
| Min length | 3 characters |
| Max length | 120 characters |
| Start char | Must begin with a letter or number |
| Duplicate | Case-insensitive, checked server-side |
| Priority | Must be `high`, `medium`, or `low` |

All rules run **client-side** (instant feedback) and **server-side** (always enforced).

---

## Agile Notes

- Each `src/` subfolder maps to a bounded context (`api`, `models`, `utils`)
- Business logic lives only in `TaskModel.php` — controllers stay thin
- `Validator.php` and `Response.php` are shared utilities, sprint-reusable
- `public/assets/js/app.js` uses IIFE modules — swap any module independently
- `tests/` grows per sprint; add a test before fixing any bug

---

## Tech Stack

| Layer | Tech |
|-------|------|
| Frontend | HTML5, CSS3 (custom properties), Vanilla JS (IIFE modules) |
| Backend | PHP 8.1+ (PDO, OOP) |
| Database | MySQL 8.0+ |
| Fonts | Space Grotesk + Inter (Google Fonts) |
| Server | Apache / Nginx / PHP built-in |
