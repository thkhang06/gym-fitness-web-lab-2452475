<?php
/**
 * TIGER FITNESS - Netlify Static Bundle Generator (Updated for Lab 3)
 * Exports dynamic PHP/MySQL pages into a standalone, production-ready static site
 * Output directory: netlify_dist/
 *
 * Usage:
 *   php build_netlify.php
 *   php build_netlify.php --base-url=http://127.0.0.1:8080/LAB1.3_CC01_2452475/bookstore
 */

$baseUrl = getenv('TIGER_BASE_URL') ?: 'http://localhost/LAB1.3_CC01_2452475/bookstore';
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $baseUrl = rtrim(substr($arg, 11), '/');
    }
}

$rootDir = __DIR__;
$distDir = $rootDir . '/netlify_dist';

foreach ([$distDir, $distDir . '/css', $distDir . '/js', $distDir . '/images'] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

echo "[1/4] Copying CSS, images, sitemap, robots, and brand assets to netlify_dist/...\n";
copy($rootDir . '/bookstore/public/css/style.css', $distDir . '/css/style.css');
if (file_exists($rootDir . '/bookstore/public/robots.txt')) {
    copy($rootDir . '/bookstore/public/robots.txt', $distDir . '/robots.txt');
}
if (file_exists($rootDir . '/bookstore/public/sitemap.xml')) {
    $sitemap = file_get_contents($rootDir . '/bookstore/public/sitemap.xml');
    $sitemap = str_replace('/LAB1.3_CC01_2452475/bookstore/public/index.php', '/index.html', $sitemap);
    $sitemap = str_replace('/LAB1.3_CC01_2452475/bookstore/pages/classes.php', '/classes.html', $sitemap);
    $sitemap = str_replace('/LAB1.3_CC01_2452475/bookstore/pages/bookings.php', '/bookings.html', $sitemap);
    $sitemap = preg_replace('/\/LAB1\.3_CC01_2452475\/bookstore\/pages\/item_details\.php\?id=(\d+)/', '/item_details_$1.html', $sitemap);
    file_put_contents($distDir . '/sitemap.xml', $sitemap);
}

$imageFiles = glob($rootDir . '/bookstore/public/images/*.*');
foreach ($imageFiles as $img) {
    copy($img, $distDir . '/images/' . basename($img));
}

echo "[2/4] Generating client-side netlify-interactive.js...\n";
$interactiveJs = <<<'JS'
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
JS;

file_put_contents($distDir . '/js/netlify-interactive.js', $interactiveJs);

function fetchPage($url, $shareHandle = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($shareHandle) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
curl_setopt($ch, CURLOPT_SHARE, $shareHandle);
    }
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function cleanStaticHtml($html) {
    $replacements = [
        '../public/index.php'    => 'index.html',
        'public/index.php'       => 'index.html',
        'index.php'              => 'index.html',
        '../pages/classes.php'   => 'classes.html',
        'pages/classes.php'      => 'classes.html',
        'classes.php'            => 'classes.html',
        '../pages/bookings.php'  => 'bookings.html',
        'pages/bookings.php'     => 'bookings.html',
        'bookings.php'           => 'bookings.html',
        '../pages/dashboard.php' => 'dashboard.html',
        'pages/dashboard.php'    => 'dashboard.html',
        'dashboard.php'          => 'dashboard.html',
        '../pages/class_form.php'=> 'class_form.html',
        'pages/class_form.php'   => 'class_form.html',
        'class_form.php'         => 'class_form.html',
        '../auth/login.php'      => 'login.html',
        'auth/login.php'         => 'login.html',
        'login.php'              => 'login.html',
        '../auth/register.php'   => 'register.html',
        'auth/register.php'      => 'register.html',
        'register.php'           => 'register.html',
        '../auth/logout.php'     => 'login.html',
        'auth/logout.php'        => 'login.html',
        'logout.php'             => 'login.html',
        '../public/sitemap.xml'  => 'sitemap.xml',
    ];

    for ($i = 1; $i <= 10; $i++) {
        $replacements['../pages/item_details.php?id=' . $i] = 'item_details_' . $i . '.html';
        $replacements['pages/item_details.php?id=' . $i]    = 'item_details_' . $i . '.html';
        $replacements['item_details.php?id=' . $i]          = 'item_details_' . $i . '.html';
    }

    foreach ($replacements as $search => $replace) {
        $html = str_replace($search, $replace, $html);
    }

    $assetFixes = [
        '../public/css/style.css'      => 'css/style.css',
        'public/css/style.css'         => 'css/style.css',
        '../public/images/favicon.svg' => 'images/favicon.svg',
        'public/images/favicon.svg'    => 'images/favicon.svg',
        '../public/images/'            => 'images/',
        'public/images/'               => 'images/',
    ];
    foreach ($assetFixes as $s => $r) {
        $html = str_replace($s, $r, $html);
    }

    $html = preg_replace('/href="(?:\.\.\/pages\/|pages\/)?set_theme\.php[^"]*"/', 'href="javascript:void(0)" onclick="toggleNetlifyTheme()"', $html);
    $html = preg_replace('/src="(?:\.\.\/)?public\/(images\/[^"]+)"/', 'src="$1"', $html);
    $html = preg_replace('/<script src="(?:\.\.\/public\/)?js\/script\.js"><\/script>\s*/', '', $html);
    $html = preg_replace('/\s*<meta name="csrf-token" content="[^"]*">\r?\n?/', "\n", $html);
    $html = preg_replace('/https?:\/\/(?:127\.0\.0\.1:\d+|localhost(?::\d+)?)\/LAB1\.3_CC01_2452475\/bookstore\//', '', $html);

    $jsInjection = '<script src="js/netlify-interactive.js"></script>' . "\n" . '</body>';
    $html = str_replace('</body>', $jsInjection, $html);

    return $html;
}

echo "[3/4] Exporting pages from {$baseUrl}...\n";

// Phase A: Export guest/public pages first (so login.html and register.html render forms cleanly)
$publicPages = [
    'index.html'    => $baseUrl . '/public/index.php',
    'classes.html'  => $baseUrl . '/pages/classes.php',
    'login.html'    => $baseUrl . '/auth/login.php',
    'register.html' => $baseUrl . '/auth/register.php',
];
for ($id = 1; $id <= 6; $id++) {
    $publicPages['item_details_' . $id . '.html'] = $baseUrl . '/pages/item_details.php?id=' . $id;
}

// Phase B: Authenticate as Admin with CSRF token using shared in-memory cookie store
$shareHandle = curl_share_init();
curl_share_setopt($shareHandle, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE);

$loginUrl = $baseUrl . '/auth/login.php';
$loginPage = fetchPage($loginUrl, $shareHandle);
$csrfToken = '';
if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', (string)$loginPage, $m)) {
    $csrfToken = $m[1];
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $csrfToken,
    'email'      => 'admin@gymfitness.local',
    'password'   => 'Password123!'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, '');
curl_setopt($ch, CURLOPT_SHARE, $shareHandle);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$loginResp = curl_exec($ch);
curl_close($ch);
echo "  Admin Auth for protected pages: " . (strpos((string)$loginResp, 'Athlete Dashboard') !== false ? "OK" : "FAILED") . "\n";

$protectedPages = [
    'bookings.html'   => $baseUrl . '/pages/bookings.php',
    'dashboard.html'  => $baseUrl . '/pages/dashboard.php',
    'class_form.html' => $baseUrl . '/pages/class_form.php',
];

$exportSet = [
    ['pages' => $publicPages,    'useAuth' => false],
    ['pages' => $protectedPages, 'useAuth' => true],
];

foreach ($exportSet as $group) {
    foreach ($group['pages'] as $filename => $url) {
        echo "  Exporting $filename...\n";
        $rawHtml = $group['useAuth']
            ? fetchPage($url, $shareHandle)
            : fetchPage($url, null);
        if (!$rawHtml) {
            echo "    WARNING: Failed to fetch $url\n";
            continue;
        }

        if ($filename === 'classes.html') {
            $rawHtml = preg_replace_callback(
                '/<div class="col-12 col-md-6 col-lg-4">(\s*<article class="class-card h-100">.*?<\/article>\s*<\/div>)/s',
                function($matches) {
                    preg_match('/<h3[^>]*card-title[^>]*>(.*?)<\/h3>/s', $matches[1], $tMatch);
                    preg_match('/<span class="class-discipline-badge">(.*?)<\/span>/s', $matches[1], $cMatch);
                    $title = trim(strip_tags($tMatch[1] ?? ''));
                    $cat = trim(strip_tags($cMatch[1] ?? ''));
                    $catId = '1';
                    if (stripos($cat, 'Cardio') !== false || stripos($cat, 'Conditioning') !== false) $catId = '2';
                    elseif (stripos($cat, 'Mobility') !== false || stripos($cat, 'Core') !== false) $catId = '3';
                    elseif (stripos($cat, 'Combat') !== false || stripos($cat, 'Martial') !== false) $catId = '4';

                    return '<div class="col-12 col-md-6 col-lg-4 class-card-item" data-title="' . htmlspecialchars($title) . '" data-category="' . $catId . '">' . $matches[1];
                },
                $rawHtml
            );
        }

        file_put_contents($distDir . '/' . $filename, cleanStaticHtml($rawHtml));
    }
}

fetchPage($baseUrl . '/auth/logout.php', $shareHandle);
curl_share_close($shareHandle);

echo "[4/4] Generating netlify.toml configuration in root directory...\n";
$netlifyToml = <<<'TOML'
# Netlify Deployment Configuration for TIGER FITNESS
[build]
  publish = "netlify_dist"

[[redirects]]
  from = "/"
  to = "/index.html"
  status = 200

[[redirects]]
  from = "/classes"
  to = "/classes.html"
  status = 200

[[redirects]]
  from = "/bookings"
  to = "/bookings.html"
  status = 200

[[redirects]]
  from = "/dashboard"
  to = "/dashboard.html"
  status = 200

[[redirects]]
  from = "/login"
  to = "/login.html"
  status = 200

[[redirects]]
  from = "/register"
  to = "/register.html"
  status = 200

[[headers]]
  for = "/*"
  [headers.values]
    X-Frame-Options = "DENY"
    X-XSS-Protection = "1; mode=block"
    X-Content-Type-Options = "nosniff"
    Referrer-Policy = "strict-origin-when-cross-origin"

[[headers]]
  for = "/images/*"
  [headers.values]
    Cache-Control = "public, max-age=31536000, immutable"
TOML;

file_put_contents($rootDir . '/netlify.toml', $netlifyToml);

echo "\nSUCCESS! Standalone static Netlify bundle successfully built at: $distDir\n";
