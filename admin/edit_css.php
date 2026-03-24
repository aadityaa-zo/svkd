<?php
require_once 'config.php';
check_login();

$cssPath = dirname(__DIR__) . '/styles.css';
$saveSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['css_content'])) {
    file_put_contents($cssPath, $_POST['css_content']);
    $saveSuccess = true;
}

$cssContent = file_get_contents($cssPath);

include 'header.php';
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-family: 'Outfit'; font-size: 1.5rem; margin-bottom: 0.5rem;">Appearance (style.css)</h2>
    <p style="color: var(--text-dim); font-size: 0.9rem;">Modify your main website's global styles directly.</p>
</div>

<?php if ($saveSuccess): ?>
    <div class="alert" style="background: rgba(50, 215, 75, 0.1); color: var(--success); border: 1px solid rgba(50, 215, 75, 0.2); margin-bottom: 1.5rem;">
        Styles saved successfully!
    </div>
<?php endif; ?>

<div class="glass-card">
    <form action="edit_css.php" method="POST">
        <textarea name="css_content" style="width: 100%; height: 500px; background: rgba(0,0,0,0.3); color: #82aaff; border: 1px solid var(--glass-border); border-radius: 8px; padding: 1.5rem; font-family: 'NaturalMono-Regular', 'Courier New', monospace; font-size: 14px; line-height: 1.6; outline: none; resize: vertical;"><?= htmlspecialchars($cssContent) ?></textarea>
        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
