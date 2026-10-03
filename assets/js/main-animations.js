/**
 * Smart Library Management System - Interactive Animations & UI Enhancements
 * Includes: Scroll-reveal, animated counters, FAQ accordions, floating back-to-top, and toast notifications.
 */

document.addEventListener('DOMContentLoaded', () => {
  initScrollAnimations();
  initNavbarElevation();
  initBackToTopButton();
  initAnimatedCounters();
  initFaqAccordion();
});

/* =====================================================
   1. SCROLL REVEAL (IntersectionObserver)
===================================================== */
function initScrollAnimations() {
  const revealElements = document.querySelectorAll(
    '.service-card, .catalog-card, .book, .project-card, .mission-box, .values-box, .stat-card, .faq-item, .contact-container'
  );

  if (!revealElements.length) return;

  const windowHeight = window.innerHeight;
  const unrevealed = [];

  revealElements.forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.top < windowHeight + 80) {
      el.classList.add('revealed');
    } else {
      el.classList.add('reveal-on-scroll');
      unrevealed.push(el);
    }
  });

  if (!unrevealed.length) return;

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed');
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.05, rootMargin: '0px 0px 60px 0px' });

  unrevealed.forEach(el => observer.observe(el));
}

/* =====================================================
   2. NAVBAR ELEVATION ON SCROLL
===================================================== */
function initNavbarElevation() {
  const header = document.querySelector('.main-header');
  if (!header) return;

  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      header.classList.add('scrolled-header');
    } else {
      header.classList.remove('scrolled-header');
    }
  });
}

/* =====================================================
   3. FLOATING BACK-TO-TOP BUTTON
===================================================== */
function initBackToTopButton() {
  if (document.getElementById('backToTopBtn')) return;

  const btn = document.createElement('button');
  btn.id = 'backToTopBtn';
  btn.className = 'back-to-top-btn';
  btn.setAttribute('aria-label', 'Back to top');
  btn.innerHTML = '&#8679;';
  document.body.appendChild(btn);

  window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
      btn.classList.add('visible');
    } else {
      btn.classList.remove('visible');
    }
  });

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

/* =====================================================
   4. ANIMATED NUMBER COUNTERS
===================================================== */
function initAnimatedCounters() {
  const statNumbers = document.querySelectorAll('.stat-number');
  if (!statNumbers.length) return;

  const counterObserver = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const text = el.innerText.trim();
        const numericVal = parseInt(text.replace(/\D/g, ''), 10);

        if (!isNaN(numericVal) && numericVal > 0 && !el.dataset.animated) {
          el.dataset.animated = 'true';
          let count = 0;
          const duration = 1200;
          const stepTime = Math.max(Math.floor(duration / numericVal), 20);

          const timer = setInterval(() => {
            count += Math.ceil(numericVal / (duration / stepTime));
            if (count >= numericVal) {
              count = numericVal;
              clearInterval(timer);
              el.innerText = text;
            } else {
              el.innerText = text.replace(numericVal.toString(), count.toString());
            }
          }, stepTime);
        }
        obs.unobserve(el);
      }
    });
  }, { threshold: 0.5 });

  statNumbers.forEach(num => counterObserver.observe(num));
}

/* =====================================================
   5. INTERACTIVE FAQ ACCORDION (For services.html)
===================================================== */
function initFaqAccordion() {
  const faqItems = document.querySelectorAll('.faq-item');
  if (!faqItems.length) return;

  faqItems.forEach(item => {
    const question = item.querySelector('h4');
    const answer = item.querySelector('p');

    if (question && answer) {
      question.classList.add('faq-toggle');
      question.innerHTML += ' <span class="faq-icon">+</span>';

      question.addEventListener('click', () => {
        const isOpen = item.classList.contains('open');
        
        // Close other FAQs
        faqItems.forEach(other => {
          other.classList.remove('open');
          const icon = other.querySelector('.faq-icon');
          if (icon) icon.innerText = '+';
        });

        if (!isOpen) {
          item.classList.add('open');
          const icon = item.querySelector('.faq-icon');
          if (icon) icon.innerText = '-';
        }
      });
    }
  });
}

/* =====================================================
   6. CUSTOM TOAST NOTIFICATION
===================================================== */
function showToast(message, type = 'success') {
  let toast = document.getElementById('slmsToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'slmsToast';
    toast.className = 'slms-toast';
    document.body.appendChild(toast);
  }

  toast.className = `slms-toast ${type} show`;
  toast.innerText = message;

  setTimeout(() => {
    toast.classList.remove('show');
  }, 3500);
}
