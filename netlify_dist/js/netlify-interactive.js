/**
 * TIGER FITNESS - Netlify Client-Side Interactive Engine
 * Provides Theme Switching, Live Catalog Filtering & Static Demo Interactions
 */
(function() {
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

    document.querySelectorAll('.theme-toggle-btn').forEach(function(btn) {
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
    applyTheme(current === 'dark' ? 'light' : 'dark');
  };

  var savedTheme = localStorage.getItem('apex_theme') || 'dark';
  applyTheme(savedTheme);

  document.addEventListener('DOMContentLoaded', function() {
    applyTheme(savedTheme);

    document.querySelectorAll('a[href*="set_theme.php"], a[href*="toggleNetlifyTheme"], .theme-toggle-btn').forEach(function(el) {
      el.removeAttribute('href');
      el.style.cursor = 'pointer';
      el.classList.add('theme-toggle-btn');
      el.addEventListener('click', function(e) {
        e.preventDefault();
        window.toggleNetlifyTheme();
      });
    });

    // Live Client-Side Filtering on classes.html
    var searchInput = document.querySelector('input[name="search"]');
    var categorySelect = document.querySelector('select[name="category_id"]');
    var trainerSelect = document.querySelector('select[name="trainer"]');
    var classCards = document.querySelectorAll('.class-card-item');
    var countEl = document.getElementById('classes-visible-count');

    function filterClasses() {
      if (!classCards.length) return;
      var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
      var selectedCat = categorySelect ? categorySelect.value : '';
      var selectedTrainer = trainerSelect ? trainerSelect.value : '';
      var visibleCount = 0;

      classCards.forEach(function(item) {
        var title = (item.getAttribute('data-title') || '').toLowerCase();
        var cat = item.getAttribute('data-category') || '';
        var coach = (item.getAttribute('data-coach') || '').toLowerCase();
        var desc = (item.getAttribute('data-desc') || '').toLowerCase();

        var matchesQuery = !query || title.includes(query) || coach.includes(query) || desc.includes(query);
        var matchesCat = !selectedCat || cat === selectedCat;
        var matchesTrainer = !selectedTrainer || coach === selectedTrainer.toLowerCase();

        if (matchesQuery && matchesCat && matchesTrainer) {
          item.style.display = '';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      });

      if (countEl) countEl.textContent = String(visibleCount);
    }

    if (searchInput) searchInput.addEventListener('input', filterClasses);
    if (categorySelect) categorySelect.addEventListener('change', filterClasses);
    if (trainerSelect) trainerSelect.addEventListener('change', filterClasses);

    var urlParams = new URLSearchParams(window.location.search);
    var catParam = urlParams.get('category_id');
    if (catParam && categorySelect) {
      categorySelect.value = catParam;
      filterClasses();
    }

    var filterForm = document.getElementById('ajax-class-filter-form');
    if (filterForm) {
      filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        filterClasses();
      });
    }

    var loginForm = document.querySelector('form[action*="login"]');
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        window.location.href = 'dashboard.html';
      });
    }

    var classForm = document.querySelector('form[action*="class_form"]');
    if (classForm) {
      classForm.addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Demo mode: Class saved in static preview!');
        window.location.href = 'classes.html';
      });
    }

    var capOutput = document.getElementById('live-capacity-output');
    if (capOutput && capOutput.textContent.indexOf('Loading') !== -1) {
      capOutput.innerHTML = '<div class="small text-secondary">Static preview: run via XAMPP PHP/MySQL for live transactional capacity checks.</div>';
    }
  });
})();