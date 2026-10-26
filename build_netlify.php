<?php
/**
 * TIGER FITNESS - Netlify Static Bundle Generator
 * Exports dynamic PHP/MySQL pages into a standalone, production-ready static site
 * Output directory: netlify_dist/
 */

$baseUrl = 'http://localhost/LAB1.3_CC01_2452475/bookstore';
$rootDir = __DIR__;
$distDir = $rootDir . '/netlify_dist';
$scratchDir = __DIR__ . '/scratch';
$tempCookieJar = $scratchDir . '/netlify_export_cookies.txt';

if (!file_exists($distDir)) {
    mkdir($distDir, 0777, true);
}
if (!file_exists($distDir . '/css')) {
    mkdir($distDir . '/css', 0777, true);
}
if (!file_exists($distDir . '/js')) {
    mkdir($distDir . '/js', 0777, true);
}
if (!file_exists($distDir . '/images')) {
    mkdir($distDir . '/images', 0777, true);
}

// 1. Copy Static Assets
echo "[1/4] Copying CSS, images, and brand assets to netlify_dist/...\n";
copy($rootDir . '/bookstore/public/css/style.css', $distDir . '/css/style.css');

$imageFiles = glob($rootDir . '/bookstore/public/images/*.*');
foreach ($imageFiles as $img) {
    copy($img, $distDir . '/images/' . basename($img));
}

// 2. Generate Interactive Client-side JS for Netlify (Theme toggle, filter, demo auth)
echo "[2/4] Generating client-side netlify-interactive.js...\n";
$interactiveJs = <<<'JS'
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
JS;

file_put_contents($distDir . '/js/netlify-interactive.js', $interactiveJs);

// Helper to fetch HTTP content with curl
function fetchPage($url, $cookieJar = null, $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    }
    if ($cookieFile && file_exists($cookieFile)) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// Transform dynamic PHP URLs to static HTML and uniform relative asset paths
function cleanStaticHtml($html, $pageName = '') {
    // 1. Transform PHP page routes to HTML
    $replacements = [
        '../public/index.php'            => 'index.html',
        'public/index.php'               => 'index.html',
        'index.php'                      => 'index.html',
        '../pages/classes.php'           => 'classes.html',
        'pages/classes.php'              => 'classes.html',
        'classes.php'                    => 'classes.html',
        '../pages/dashboard.php'         => 'dashboard.html',
        'pages/dashboard.php'            => 'dashboard.html',
        'dashboard.php'                  => 'dashboard.html',
        '../pages/class_form.php'        => 'class_form.html',
        'pages/class_form.php'           => 'class_form.html',
        'class_form.php'                 => 'class_form.html',
        '../auth/login.php'              => 'login.html',
        'auth/login.php'                 => 'login.html',
        'login.php'                      => 'login.html',
        '../auth/register.php'           => 'register.html',
        'auth/register.php'              => 'register.html',
        'register.php'                   => 'register.html',
        '../auth/logout.php'             => 'login.html',
        'auth/logout.php'                => 'login.html',
        'logout.php'                     => 'login.html',
    ];

    // Item details mappings (1 through 6)
    for ($i = 1; $i <= 10; $i++) {
        $replacements['../pages/item_details.php?id=' . $i] = 'item_details_' . $i . '.html';
        $replacements['pages/item_details.php?id=' . $i]    = 'item_details_' . $i . '.html';
        $replacements['item_details.php?id=' . $i]          = 'item_details_' . $i . '.html';
    }

    // Category filter query params in classes
    for ($c = 1; $c <= 4; $c++) {
        $replacements['classes.html?category_id=' . $c] = 'classes.html?category_id=' . $c;
    }

    foreach ($replacements as $search => $replace) {
        $html = str_replace($search, $replace, $html);
    }

    // 2. Uniform Asset Paths (CSS, Images, Favicon)
    $assetFixes = [
        '../public/css/style.css' => 'css/style.css',
        'public/css/style.css'    => 'css/style.css',
        '../public/images/'       => 'images/',
        'public/images/'          => 'images/',
        '../public/images/favicon.svg' => 'images/favicon.svg',
        'public/images/favicon.svg'    => 'images/favicon.svg',
    ];
    foreach ($assetFixes as $s => $r) {
        $html = str_replace($s, $r, $html);
    }

    // Replace set_theme.php link with direct JS trigger
    $html = preg_replace('/href="(?:\.\.\/pages\/|pages\/)?set_theme\.php[^"]*"/', 'href="javascript:void(0)" onclick="toggleNetlifyTheme()"', $html);

    // Fix remaining item images if still prefixed
    $html = preg_replace('/src="(?:\.\.\/)?public\/(images\/[^"]+)"/', 'src="$1"', $html);

    // 3. Inject Netlify Interactive JS right before </body>
    $jsInjection = '<script src="js/netlify-interactive.js"></script>' . "\n" . '</body>';
    $html = str_replace('</body>', $jsInjection, $html);

    return $html;
}

echo "[3/4] Exporting pages from local server...\n";

// Authenticate as Admin first so dashboard.php and class_form.php can be fully exported
@unlink($tempCookieJar);
$loginUrl = $baseUrl . '/auth/login.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => 'admin@gymfitness.local',
    'password' => 'Password123!'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $tempCookieJar);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_exec($ch);
curl_close($ch);

$pagesToExport = [
    'index.html'      => $baseUrl . '/public/index.php',
    'classes.html'    => $baseUrl . '/pages/classes.php',
    'login.html'      => $baseUrl . '/auth/login.php',
    'register.html'   => $baseUrl . '/auth/register.php',
    'dashboard.html'  => $baseUrl . '/pages/dashboard.php',
    'class_form.html' => $baseUrl . '/pages/class_form.php',
];

// Add individual class details pages (1 to 6)
for ($id = 1; $id <= 6; $id++) {
    $pagesToExport['item_details_' . $id . '.html'] = $baseUrl . '/pages/item_details.php?id=' . $id;
}

foreach ($pagesToExport as $filename => $url) {
    echo "  Exporting $filename from $url...\n";
    $rawHtml = fetchPage($url, $tempCookieJar, $tempCookieJar);
    if (!$rawHtml) {
        echo "    WARNING: Failed to fetch $url\n";
        continue;
    }

    // Special augmentation for classes.html to add data-* attributes for instant JS client filtering
    if ($filename === 'classes.html') {
        $rawHtml = preg_replace_callback(
            '/<div class="col-12 col-md-6 col-lg-4">(\s*<div class="class-card h-100">.*?<\/div>\s*<\/div>)/s',
            function($matches) {
                // Extract title and category
                preg_match('/<h5[^>]*class="card-title[^>]*>(.*?)<\/h5>/s', $matches[1], $tMatch);
                preg_match('/<span class="class-discipline-badge">(.*?)<\/span>/s', $matches[1], $cMatch);
                $title = trim(strip_tags($tMatch[1] ?? ''));
                $cat = trim(strip_tags($cMatch[1] ?? ''));
                
                // Map category name to ID
                $catId = '1';
                if (stripos($cat, 'Cardio') !== false || stripos($cat, 'Conditioning') !== false) $catId = '2';
                elseif (stripos($cat, 'Mobility') !== false || stripos($cat, 'Core') !== false) $catId = '3';
                elseif (stripos($cat, 'Combat') !== false || stripos($cat, 'Martial') !== false) $catId = '4';

                return '<div class="col-12 col-md-6 col-lg-4 class-card-item" data-title="' . htmlspecialchars($title) . '" data-category="' . $catId . '">' . $matches[1];
            },
            $rawHtml
        );
    }

    $cleanHtml = cleanStaticHtml($rawHtml, $filename);
    file_put_contents($distDir . '/' . $filename, $cleanHtml);
}

@unlink($tempCookieJar);

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
echo "Root netlify.toml generated pointing to publish = 'netlify_dist'. Ready for zero-config Netlify deploy!\n";
