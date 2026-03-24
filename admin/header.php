<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dev Adhvaryu</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="logo">ADMIN</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="projects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : '' ?>">Manage Work</a>
                </li>
                <li class="nav-item">
                    <a href="files.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'files.php' ? 'active' : '' ?>">Files</a>
                </li>
                <li class="nav-item">
                    <a href="edit_css.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'edit_css.php' ? 'active' : '' ?>">Appearance</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">Logout</a>
                </li>
            </ul>
        </aside>
        <main class="main-content">
            <header>
                <h1>Welcome Back</h1>
                <div class="user-info">
                    <span style="color: var(--text-dim)"><?= ADMIN_USER ?></span>
                </div>
            </header>
