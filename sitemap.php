<?php
// Dynamic sitemap — serves as sitemap.xml via .htaccess rewrite
header('Content-Type: application/xml; charset=utf-8');
include('includes/siteBootstrap.inc.php');

$base = 'https://uru.soccer';
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = [
    ['', '1.0', 'weekly'],
    ['playerProfiles.php', '0.9', 'weekly'],
    ['training.php', '0.8', 'monthly'],
    ['services.php', '0.7', 'monthly'],
    ['aboutUs.php', '0.6', 'monthly'],
    ['contactUs.php', '0.5', 'monthly'],
];
foreach ($staticPages as [$path, $priority, $freq]) {
    echo "  <url>\n";
    echo "    <loc>{$base}" . ($path ? "/{$path}" : '/') . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$freq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

// Active player profiles
mysqli_query($cn, "ALTER TABLE PP_PLAYERS ADD COLUMN IF NOT EXISTS URL_SLUG VARCHAR(200) NULL");
$pr = mysqli_query($cn, "SELECT ID, FIRST_NAME, LAST_NAME, URL_SLUG FROM PP_PLAYERS WHERE IS_ACTIVE=1 ORDER BY LAST_NAME, FIRST_NAME");
while ($p = mysqli_fetch_assoc($pr)) {
    $slug = !empty($p['URL_SLUG']) ? $p['URL_SLUG'] : strtolower($p['FIRST_NAME'] . '-' . $p['LAST_NAME']);
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    echo "  <url>\n";
    echo "    <loc>{$base}/{$slug}</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
