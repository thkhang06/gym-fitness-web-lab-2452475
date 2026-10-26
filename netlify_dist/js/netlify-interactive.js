/**
 * TIGER FITNESS - Netlify Client-Side Interactive Engine
 * Provides Theme Switching, Live Catalog Filtering & Demo Auth on static hosting
 */

(function() {
  // 1. Dark / Light Mode Switching via localStorage
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (document.body) {
      if (theme === 'dark') {
        document.body.classList.add('theme-dark');
      } else {
        document.body.classList.remove('theme-dark');
      }
    }
    localStorage.setItem('apex_theme', theme);

    // Update toggle button text & icon if present
    var toggleBtns = document.querySelectorAll('.theme-toggle-btn');
    toggleBtns.forEach(function(btn) {
      if (theme === 'dark') {
        btn.innerHTML = '<span class="text-warning">&#9728;</span> <span class="small font-headline">LIGHT</span>';
        btn.setAttribute('title', 'Switch to Light Mode');
      } else {
        btn.innerHTML = '<span class="text-info">&#9790;</span> <span class="small font-headline">DARK MODE</span>';
        btn.setAttribute('title', 'Switch to Dark Mode');
      }
    });
  }

  window.toggleNetlifyTheme = function() {
    var current = localStorage.getItem('apex_theme') || 'dark';
    var next = (current === 'dark') ? 'light' : 'dark';
    applyTheme(next);
  };

  var savedTheme = localStorage.getItem('apex_theme') || 'dark';
  applyTheme(savedTheme);

  document.addEventListener('DOMContentLoaded', function() {
    applyTheme(savedTheme);

    // Hook up any theme toggle links
    var themeLinks = document.querySelectorAll('a[href*="set_theme.php"], .theme-toggle-btn');
    themeLinks.forEach(function(el) {
      el.removeAttribute('href');
      el.style.cursor = 'pointer';
      el.classList.add('theme-toggle-btn');
      el.addEventListener('click', function(e) {
        e.preventDefault();
        window.toggleNetlifyTheme();
      });
    });

    // 2. Client-Side Catalog Search & Category Filter for classes.html
    var searchInput = document.querySelector('input[name="search"]');
    var categorySelect = document.querySelector('select[name="category_id"]');
    var classCards = document.querySelectorAll('.class-card-item');

    function filterClasses() {
      if (!classCards.length) return;
      var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
      var selectedCat = (categorySelect ? categorySelect.value : '');

      var visibleCount = 0;
      classCards.forEach(function(item) {
        var title = (item.getAttribute('data-title') || '').toLowerCase();
        var cat = item.getAttribute('data-category') || '';
        var coach = (item.getAttribute('data-coach') || '').toLowerCase();
        var desc = (item.getAttribute('data-desc') || '').toLowerCase();

        var matchesQuery = !query || title.includes(query) || coach.includes(query) || desc.includes(query);
        var matchesCat = !selectedCat || cat === selectedCat;

        if (matchesQuery && matchesCat) {
          item.style.display = '';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });
    }

    if (searchInput) {
      searchInput.addEventListener('input', filterClasses);
    }
    if (categorySelect) {
      categorySelect.addEventListener('change', filterClasses);
    }

    // Check query params on load (e.g. ?category_id=2)
    var urlParams = new URLSearchParams(window.location.search);
    var catParam = urlParams.get('category_id');
    if (catParam && categorySelect) {
      categorySelect.value = catParam;
      filterClasses();
    }

    // 3. Demo Login Simulation on login.html
    var loginForm = document.querySelector('form[action*="login.php"], form[action*="login.html"]');
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var emailInput = document.getElementById('email');
        var email = emailInput ? emailInput.value : 'admin@gymfitness.local';
        localStorage.setItem('tiger_logged_in', 'true');
        localStorage.setItem('tiger_user_email', email);
        window.location.href = 'dashboard.html';
      });
    }

    // 4. Demo Class Form on class_form.html
    var classForm = document.querySelector('form[action*="class_form"]');
    if (classForm) {
      classForm.addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Demo mode: Class saved successfully in Netlify preview!');
        window.location.href = 'classes.html';
      });
    }
  });
})();