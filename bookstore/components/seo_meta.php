<?php
/**
 * Reusable SEO Metadata, Open Graph, Structured Data (JSON-LD) & CSRF Meta Component
 * Lab 3 Tasks 4 & 5
 *
 * Expects optional $seo = ['title', 'description', 'keywords', 'robots', 'type', 'schema']
 */
$seoTitle = $seo['title'] ?? 'TIGER FITNESS - Strength, Conditioning & Athletic Training Center';
$seoDescription = $seo['description'] ?? 'Tiger Fitness Center offers strength barbell coaching, HIIT metabolic conditioning, yoga mobility, and combat sports training with live class scheduling and online booking.';
$seoKeywords = $seo['keywords'] ?? 'Tiger Fitness, gym classes, strength training, HIIT conditioning, Olympic weightlifting, fitness class booking, personal training';
$seoRobots = $seo['robots'] ?? 'index, follow';
$seoType = $seo['type'] ?? 'website';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonicalUrl = $scheme . '://' . $host . $requestPath;
if (!empty($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $canonicalUrl .= '?id=' . urlencode((string)$_GET['id']);
}

$defaultSchema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'ExerciseGym',
    'name'        => 'TIGER FITNESS Center',
    'description' => $seoDescription,
    'url'         => $canonicalUrl,
    'telephone'   => '+84-28-3864-7256',
    'address'     => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => '268 Ly Thuong Kiet, Ward 14, District 10',
        'addressLocality' => 'Ho Chi Minh City',
        'addressCountry'  => 'VN',
    ],
    'openingHours' => 'Mo-Su 05:00-23:00',
];
$jsonLd = $seo['schema'] ?? $defaultSchema;
?>
  <title><?php echo e($seoTitle); ?></title>
  <meta name="description" content="<?php echo e($seoDescription); ?>">
  <meta name="keywords" content="<?php echo e($seoKeywords); ?>">
  <meta name="author" content="Thai Khang - MSSV 2452475 (HCMUT)">
  <meta name="robots" content="<?php echo e($seoRobots); ?>">
  <meta name="csrf-token" content="<?php echo e(getCsrfToken()); ?>">
  <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">

  <!-- Open Graph / Social SEO Tags -->
  <meta property="og:site_name" content="TIGER FITNESS Center">
  <meta property="og:type" content="<?php echo e($seoType); ?>">
  <meta property="og:title" content="<?php echo e($seoTitle); ?>">
  <meta property="og:description" content="<?php echo e($seoDescription); ?>">
  <meta property="og:url" content="<?php echo e($canonicalUrl); ?>">

  <!-- JSON-LD Structured Data -->
  <script type="application/ld+json"><?php echo json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?></script>
