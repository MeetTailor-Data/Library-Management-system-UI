# Smart Library Management System (SLMS)

A modern, responsive, and robust **Smart Library Management System** built with **HTML5, CSS3, JavaScript (ES6+), and PHP 8.x** backed by a relational **SQL Database (MySQL / PDO / SQLite)** with ACID transactions, inventory management, and fine automation.

---

## 📌 Project Overview

The **Smart Library Management System (SLMS)** automates book cataloging, member authentication, borrowing circulation, and penalty tracking. It provides public visitors with an interactive catalog while enforcing mandatory student authentication for book borrowing and offering librarians a dedicated management control panel.

---

## 🧠 Key Features

1. **⏱️ Automated Due Date & Fine Calculation Engine:**  
   Automatically assigns a **14-day return due date** on every book issue. Computes late penalty fines dynamically (**₹5 per day overdue**) if returned after the due date.
2. **📦 Real-time Dynamic Stock Control:**  
   Atomically decrements available book copies on issue and increments them upon return using SQL transactions. Prevents checkouts when stock reaches zero.
3. **🛡️ Smart Borrow Limits & Prevention:**  
   Enforces a **maximum borrow limit of 3 books** per student and prevents duplicate checkout of the same title.
4. **👥 Role-Based Access Control (RBAC):**  
   Single-portal authentication that auto-routes **Admins** to the management dashboard and **Students** to their personal portal.
5. **🔍 Instant Live Catalog Search & Category Filter (OPAC):**  
   Search books in real time by title, author, or ISBN, with one-click category filtering and availability status badges.

---

## 🛠️ Technology Stack

* **Frontend:** HTML5, Vanilla CSS3 (Custom Design System), JavaScript (ES6+)
* **Backend:** PHP 8.x (RESTful API controllers)
* **Database & Engine:** **Relational SQL Database** (MySQL / MariaDB via PDO with SQLite auto-fallback)
* **Database Schema:** [`database.sql`](file:///c:/Users/meett/OneDrive/Desktop/Meet/All-projects/library/database.sql) (Tables: `users`, `books`, `issued_books`, `messages`)

---

## 📂 Project Structure

```
Library-Management-System/
├── index.php                   # Root Entry Point & Auto-router
├── database.sql                # Complete SQL Database Schema & Seed Data
├── vercel.json                 # Vercel Deployment & Route Configuration
├── README.md                   # Project Documentation
│
├── Pages/                      # Web Application Pages
│   ├── index.html              # Landing / Home Page
│   ├── catalog.html            # Dynamic Book Catalog (OPAC) with Live Search & Filter
│   ├── login.html              # Role-Based Login & Student Registration (Signup)
│   ├── student-dashboard.html  # Student Portal (My Borrows, Due Dates & History)
│   ├── admin-dashboard.html    # Librarian Management Panel (Inventory, Circulation, Users)
│   ├── aboutus.html            # About Library & Mission
│   ├── services.html           # Services & Interactive FAQ Accordion
│   ├── contect.html            # Contact & Inquiry Form
│   ├── blog.html               # Library Blog Feed
│   └── blog-post1.html         # Blog Article View
│
├── backend/                    # PHP 8.x Backend Engine & SQL Controllers
│   ├── config.php              # Database Credentials & Driver Configuration
│   ├── db.php                  # PDO Database Connection, Auto-Init & Fine Calculator
│   ├── auth.php                # Authentication, Registration & Session Controller
│   ├── books.php               # Book Inventory CRUD & Catalog Controller
│   ├── circulation.php         # Issue/Return Engine, Limits & Stock Sync (Transactions)
│   └── contact.php             # Contact Inquiries Handler
│
└── assets/
    ├── css/
    │   └── style.css           # Global Theme, Layout & Mobile Media Queries
    ├── js/
    │   ├── slms-store.js       # Centralized Reactive State Engine & Real-Time Sync
    │   ├── auth-session.js     # Session Management, RBAC Navbar & Hamburger Controller
    │   └── main-animations.js  # Scroll Reveal, Counter & Accordion Animations
    └── images/                 # Book Covers, Logos & Media Assets
```

---

## 🗄️ Database Setup

### Option A: Using MySQL / MariaDB (phpMyAdmin / XAMPP / CLI)
1. Open MySQL / phpMyAdmin and create/import:
   ```bash
   mysql -u root -p < database.sql
   ```
2. Configure credentials in [`backend/config.php`](file:///c:/Users/meett/OneDrive/Desktop/Meet/All-projects/library/backend/config.php) (or use environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

### Option B: Zero Configuration / SQLite
* If MySQL is not running locally, the system automatically initializes a local SQLite database (`backend/library.sqlite`) with full table structures and seed records out of the box!

---

## 🔑 Default Login Credentials

| Role | User / Student ID | Password | Access / Permissions |
|---|---|---|---|
| **Librarian (Admin)** | `ADMIN01` *(or `admin`)* | `admin123` *(or `admin`)* | Full Inventory CRUD, Issue & Return, Student List, Inquiries |
| **Student 1 (John Doe)** | `STU101` | `student123` | Borrow Books, Active Due Date Tracking, History |
| **Student 2 (Emily Clark)** | `STU102` | `student123` | Overdue Fine Tracking & History |
| **Student 3 (David Miller)** | `STU103` | `student123` | Borrow Books, Active Due Date Tracking, History |
| **New Student** | *Custom Student ID* | *Custom Pass* | Register via the **Signup** tab on `login.html` |

---

## 🚀 How to Run Locally

1. Open your terminal in the project root directory:
   ```bash
   php -S localhost:8000
   ```
2. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

---

## 👤 Author

**Meet Tailor**  
*GitHub:* [@MeetTailor-Data](https://github.com/MeetTailor-Data)
