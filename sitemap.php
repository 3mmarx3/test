<?php
require_once __DIR__ . '/admin/includes/database.php';
require_once __DIR__ . '/admin/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars(app_origin() . base_url('')) ?></loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
<?php
try {
    $stmt = $pdo->query("SELECT username, created_at FROM users WHERE status = 'active' ORDER BY id ASC");
    while ($row = $stmt->fetch()) {
        $uUrl = full_user_url($row['username']);
        $lastmod = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d');
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($uUrl) . "</loc>\n";
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }
} catch (Throwable $e) {}
?>
</urlset>
