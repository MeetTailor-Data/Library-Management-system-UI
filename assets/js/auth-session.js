/**
 * Smart Library Management System - Session & Auth Manager
 * Handles login state, guest browsing, role-based navigation, and book borrowing across all pages.
 */

// Retrieve current logged in user from localStorage
function getCurrentUser() {
  const userJson = localStorage.getItem('slms_user');
  if (userJson) {
    try {
      return JSON.parse(userJson);
    } catch (e) {
      return null;
    }
  }
  return null;
}

// Save user session
function setCurrentUser(user) {
  localStorage.setItem('slms_user', JSON.stringify(user));
}

// Logout user and redirect home
function logoutUser() {
  localStorage.removeItem('slms_user');
  alert('You have logged out successfully.');
  window.location.href = 'index.html';
}

// Update Top Navbar based on session state across all pages
function updateNavbarAuth() {
  const user = getCurrentUser();
  const authContainer = document.getElementById('navbarAuthArea');
  const navList = document.querySelector('.navbar ul');

  if (!authContainer && !navList) return;

  if (user) {
    // USER IS LOGGED IN
    if (authContainer) {
      if (user.role === 'admin') {
        const onAdminPage = window.location.pathname.includes('admin-dashboard.html');
        authContainer.innerHTML = `
          <span style="font-size: 14px; margin-right: 12px; color: #555;">Logged in: <strong>Librarian (Admin)</strong></span>
          ${!onAdminPage ? '<a href="admin-dashboard.html" class="btn-secondary" style="padding: 7px 14px; font-size: 13px; margin-right: 6px;">Admin Panel</a>' : ''}
          <button onclick="logoutUser()" class="btn-primary" style="padding: 7px 14px; font-size: 13px; border:none; cursor:pointer;">Logout</button>
        `;
      } else {
        const onStudentPage = window.location.pathname.includes('student-dashboard.html');
        const stuName = user.name || user.studentId || 'Student';
        const stuId = user.studentId || 'STU101';
        authContainer.innerHTML = `
          <span style="font-size: 14px; margin-right: 12px; color: #555;">Welcome, <strong id="headerStuName">${stuName} (${stuId})</strong></span>
          ${!onStudentPage ? '<a href="student-dashboard.html" class="btn-secondary" style="padding: 7px 14px; font-size: 13px; margin-right: 6px;">My Dashboard</a>' : ''}
          <button onclick="logoutUser()" class="btn-primary" style="padding: 7px 14px; font-size: 13px; border:none; cursor:pointer;">Logout</button>
        `;
      }
    }
  } else {
    // GUEST VISITOR
    if (authContainer) {
      authContainer.innerHTML = `
        <a href="login.html" class="btn-primary">Sign Up</a>
      `;
    }
  }
}

// Borrow book handler (Enforces mandatory student login & centralized circulation)
function attemptBorrowBook(bookTitle, isbn) {
  const user = getCurrentUser();
  
  if (!user) {
    alert('Login Required: You must log in with your Student ID to borrow books. Redirecting to login page...');
    window.location.href = 'login.html?redirect=catalog.html';
    return;
  }

  if (user.role === 'admin') {
    alert('Admins cannot borrow books as students. Please log in with a Student ID or use the Admin Issue panel.');
    return;
  }

  if (window.SLMS_STORE) {
    const res = window.SLMS_STORE.issueBook(user.studentId, isbn);
    if (!res.success) {
      alert(res.message);
      return;
    }
    alert(res.message);
  } else {
    // Fallback if store is offline
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 14);
    const formattedDueDate = dueDate.toLocaleDateString('en-GB');
    alert(`Book Issued Successfully!\n\nBook: "${bookTitle}"\nAssigned Student ID: ${user.studentId}\nReturn Due Date: ${formattedDueDate}`);
  }
  
  // Redirect to student dashboard
  window.location.href = 'student-dashboard.html';
}

// Initialize 3-Lines Mobile Hamburger Menu
function initMobileHamburgerMenu() {
  const headerFlex = document.querySelector('.header-flex');
  const navbar = document.querySelector('.navbar');
  const authArea = document.getElementById('navbarAuthArea');

  if (!headerFlex || !navbar) return;

  // Prevent duplicate hamburger button creation
  if (!document.querySelector('.hamburger-btn')) {
    const hamburgerBtn = document.createElement('button');
    hamburgerBtn.className = 'hamburger-btn';
    hamburgerBtn.setAttribute('aria-label', 'Toggle Navigation');
    hamburgerBtn.innerHTML = `
      <span></span>
      <span></span>
      <span></span>
    `;

    // Insert hamburger button right after logo in header
    const logo = headerFlex.querySelector('.logo');
    if (logo) {
      logo.after(hamburgerBtn);
    } else {
      headerFlex.appendChild(hamburgerBtn);
    }

    // Toggle dropdown menu on click
    hamburgerBtn.addEventListener('click', () => {
      hamburgerBtn.classList.toggle('active');
      navbar.classList.toggle('show');
      if (authArea) {
        authArea.classList.toggle('show');
      }
    });

    // Close mobile menu when clicking any nav link
    navbar.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        hamburgerBtn.classList.remove('active');
        navbar.classList.remove('show');
        if (authArea) {
          authArea.classList.remove('show');
        }
      });
    });
  }
}

// Run initializers on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  updateNavbarAuth();
  initMobileHamburgerMenu();
});

