# Smart Library Management System (SLMS)

A modern, responsive, and lightweight **Smart Library Management System** built with **HTML5, CSS3, JavaScript, and PHP 8.x** utilizing a **JSON-based Flat-File Data Management Engine** (Zero external database dependency).

---

## 📌 Project Overview

The **Smart Library Management System (SLMS)** automates book cataloging, member authentication, borrowing circulation, and penalty tracking. It provides public visitors with an interactive catalog while enforcing mandatory student authentication for book borrowing and offering librarians a dedicated management control panel.

---

## 🧠 Why is it "Smart"? (Key Highlights)

1. **⏱️ Automated Due Date & Fine Calculation Engine:**  
   Automatically assigns a **14-day return due date** on every book issue. Computes late penalty fines dynamically (**₹5 per day overdue**) if returned after the due date.
2. **📦 Real-time Dynamic Stock Control:**  
   Automatically decrements available book copies on issue and increments them upon return. Prevents checkouts when stock reaches zero.
3. **🛡️ Smart Borrow Limits & Prevention:**  
   Enforces a **maximum borrow limit of 3 books** per student and prevents duplicate checkout of the same title.
4. **👥 Role-Based Access Control (RBAC):**  
   Intelligent single-portal authentication that auto-routes **Admins** to the management dashboard and **Students** to their personal portal.
5. **🔍 Instant Live Catalog Search & Category Filter (OPAC):**  
   Search books in real time by title, author, or ISBN, with one-click category filtering and availability status badges.

---

## 🛠️ Technology Stack

* **Frontend:** HTML5, Vanilla CSS3 (Custom Design System), JavaScript (ES6+)
* **Backend:** PHP 8.x
* **Data Storage / Engine:** JSON-based Flat-File Database (`data/*.json`)
* **Responsive Framework:** Mobile-first architecture with animated 3-line hamburger navigation

---

## 📂 Project Structure

```
Library-Management-System/
├── index.php                   # Root Entry Point & Auto-router
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
├── backend/                    # Pure PHP 8.x Backend Engine & Controllers
│   ├── db.php                  # Core JSON Helper & Automated Fine Calculator
│   ├── auth.php                # Authentication, Registration & Session Controller
│   ├── books.php               # Book Inventory CRUD & Catalog Controller
│   ├── circulation.php         # Issue/Return Engine, Limits & Stock Sync
│   └── contact.php             # Contact Inquiries Handler
│
├── data/                       # JSON Flat-File Data Store
│   ├── users.json              # Student & Admin Credentials
│   ├── books.json              # Catalog Inventory & Live Stock Counts
│   ├── issued_books.json       # Circulation Records & Due Dates
│   └── messages.json           # Inquiries & Contact Messages
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

## 🌐 Live Deployment

* **Production URL:** [https://smartlib-mgmt.vercel.app/](https://smartlib-mgmt.vercel.app/)

---

## 🔑 Default Login Credentials

| Role | User / Student ID | Password | Access / Permissions |
|---|---|---|---|
| **Librarian (Admin)** | `ADMIN01` *(or `admin`)* | `admin` | Full Inventory CRUD, Issue & Return, Student List, Inquiries |
| **Student (Meet Tailor)** | `STU105` | `meet2006` | Borrow Books, Active Due Date Tracking, History |
| **Student 1 (John Doe)** | `STU101` | `student123` | Borrow Books, Active Due Date Tracking, History |
| **Student 2 (Emily Clark)** | `STU102` | `student123` | Overdue Fine Tracking & History |
| **New Student** | *Custom Student ID* | *Custom Pass* | Register via the **Signup** tab on `login.html` |

---

## 🚀 How to Run Locally

### Using PHP Built-in Server (Recommended - No XAMPP needed)
1. Open your terminal in the project root directory:
   ```bash
   php -S localhost:8000
   ```
2. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

---

## ☁️ Deploying on Vercel

1. Push your repository to **GitHub**.
2. Go to [Vercel](https://vercel.com) and click **"Add New Project"**.
3. Import your `Library-Management-system-UI` repository.
4. Keep the default settings and click **Deploy**.
5. The included `vercel.json` automatically routes root traffic to `Pages/index.html`.

---

## 👤 Author

**Meet Tailor**  
*GitHub:* [@MeetTailor-Data](https://github.com/MeetTailor-Data)

---

## 📄 License

This project is open-source and created for academic, educational, and development demonstration purposes.
