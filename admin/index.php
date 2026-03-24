<?php
require_once 'config.php';
check_login();

// Get total files in uploads
$files = [];
if (is_dir(UPLOAD_DIR)) {
    $scanned = scandir(UPLOAD_DIR);
    if ($scanned !== false) {
        $files = array_diff($scanned, array('.', '..'));
    }
}
$totalFiles = count($files);

$totalSize = 0;
foreach ($files as $file) {
    if (file_exists(UPLOAD_DIR . $file)) {
        $totalSize += filesize(UPLOAD_DIR . $file);
    }
}

// Convert bytes to human readable format
function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

include 'header.php';
?>

<div class="stats-grid">
    <div class="glass-card">
        <div class="stat-label">Total Files</div>
        <div class="stat-value"><?= $totalFiles ?></div>
    </div>
    <div class="glass-card">
        <div class="stat-label">Storage Used</div>
        <div class="stat-value"><?= formatBytes($totalSize) ?></div>
    </div>
    <div class="glass-card">
        <div class="stat-label">PHP Version</div>
        <div class="stat-value"><?= phpversion() ?></div>
    </div>
</div>

<div class="glass-card" style="margin-top: 2rem;">
    <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Recent Activity</h2>
    <p style="color: var(--text-dim);">New admin panel initialized. You can now manage your file storage efficiently.</p>
</div>

<?php include 'footer.php'; ?>
