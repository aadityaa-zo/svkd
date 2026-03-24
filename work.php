<?php
// AUTO-DETECT ROOT PATH (for subfolders like http://localhost/dev.com/)
$script_name = $_SERVER['SCRIPT_NAME'];
$root_dir = str_replace('work.php', '', $script_name);
// Ensure it ends with /
$base_url = rtrim($root_dir, '/') . '/';

$projectsJson = file_get_contents('data/projects.json');
$projects = json_decode($projectsJson, true);

// Support deep linking via slug
$activeProject = !empty($projects) ? $projects[0] : null;

// Parse slug from query string (sent by .htaccess) or direct param
$slug = $_GET['slug'] ?? null;
if ($slug) {
    foreach ($projects as $p) {
        if ($p['slug'] === $slug) {
            $activeProject = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work - Dev Adhvaryu</title>
    <!-- Use dynamic base URL for absolute stability -->
    <link rel="stylesheet" href="<?= $base_url ?>styles.css">
    <style>
        /* Smooth transitions for slide indicator updates */
        #slideIndicator {
            transition: opacity 0.2s ease;
        }
    </style>
</head>

<body class="work-page">
    <div class="split-layout">
        <aside class="left-sidebar">
            <nav class="menu" style="padding-bottom: 30px;">
                <a href="<?= $base_url ?>index.html" class="menu-item" data-hover="DEV ADHVARYU">DEV ADHVARYU</a>
                <a href="<?= $base_url ?>work.php" class="menu-item active" data-hover="WORK">KAAM</a>
                <a href="<?= $base_url ?>jaankaari.html" class="menu-item" data-hover="INFORMATION">JAANKAARI</a>
            </nav>
            <div class="sidebar-top">
                <div class="search-container">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round" class="search-icon">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="SEARCH" class="search-input" id="sidebarSearch">
                </div>
                <div class="view-toggle">
                    <button class="toggle-btn" id="list-toggle">LIST</button>
                    <button class="toggle-btn active-toggle" id="thumb-toggle">THUMBNAILS</button>
                </div>
            </div>

            <div class="project-list thumb-mode" id="projectList">
                <?php if (empty($projects)): ?>
                    <p style="padding: 20px; color: #555;">No projects found.</p>
                <?php else: ?>
                    <?php foreach ($projects as $index => $p): ?>
                        <a href="#" class="project-item <?= ($activeProject && $p['slug'] === $activeProject['slug']) ? 'active-project' : '' ?>" 
                           data-project-id="<?= $p['id'] ?>"
                           data-project-slug="<?= $p['slug'] ?>">
                            <img src="<?= $base_url ?>admin/uploads/<?= $p['thumbnail'] ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="project-thumb">
                            <span><?= htmlspecialchars($p['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <main class="right-content" id="slidesContent">
            <button class="back-btn" id="backToList">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                BACK
            </button>
            <div class="project-info-fixed">
                <div class="project-info-fixed-inner">
                    <p id="currentProjectTitle" class="project-info-title">
                        <?= $activeProject ? htmlspecialchars($activeProject['title']) : 'Select a project' ?>
                    </p>
                    <div class="project-info-fixed-inner-right">
                        <p id="slideIndicator" class="project-info-indicator">
                            <?= $activeProject ? '1 / ' . count($activeProject['images']) : '0 / 0' ?>
                        </p>
                        <p class="project-info-jaankari">JAANKAARI</p>
                    </div>
                </div>
            </div>
            <div class="slides-container" id="mainSlides">
                <?php if ($activeProject): ?>
                    <?php foreach ($activeProject['images'] as $slide): 
                        // Normalize format
                        $s = is_array($slide) ? $slide : ['type' => 'image', 'src' => $slide, 'width' => 100, 'position' => 'center'];
                        $isYT = $s['type'] === 'youtube';
                        $width = $s['width'] ?? 100;
                        $position = $s['position'] ?? 'center';
                        $align = ($position === 'left' ? 'flex-start' : ($position === 'right' ? 'flex-end' : 'center'));
                    ?>
                        <div class="slide" style="width: 100%; justify-content: <?= $align ?>;">
                            <div class="slide-media-container" style="width: <?= $width ?>%;">
                                <?php if ($isYT): ?>
                                    <div class="yt-wrapper">
                                        <iframe src="https://www.youtube.com/embed/<?= $s['src'] ?>?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= $base_url ?>admin/uploads/<?= $s['src'] ?>" alt="<?= htmlspecialchars($activeProject['title']) ?>" loading="lazy">
                                    
                                    <?php if (!empty($s['yt_id'])): ?>
                                        <div class="v-overlay" style="top: <?= $s['yt_top'] ?>%; left: <?= $s['yt_left'] ?>%; width: <?= $s['yt_width'] ?>%;">
                                            <div class="yt-wrapper">
                                                <iframe src="https://www.youtube.com/embed/<?= $s['yt_id'] ?>?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Project Data for JS -->
    <script id="projectData" type="application/json">
        <?= $projectsJson ?>
    </script>

    <script>
        const projects = JSON.parse(document.getElementById('projectData').textContent);
        const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        const rootUrl = "<?= $base_url ?>";

        // Details View Logic
        const slidesContainer = document.getElementById('mainSlides');
        const slideIndicator = document.getElementById('slideIndicator');
        const projectTitleElement = document.getElementById('currentProjectTitle');
        const projectItems = document.querySelectorAll('.project-item');
        const splitLayout = document.querySelector('.split-layout');
        const backBtn = document.getElementById('backToList');

        let observer;

        function setupObserver() {
            if (observer) observer.disconnect();
            const slides = slidesContainer.querySelectorAll('.slide');
            if (slides.length === 0) return;

            observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const index = Array.from(slides).indexOf(entry.target) + 1;
                        slideIndicator.innerText = `${index} / ${slides.length}`;
                    }
                });
            }, { root: slidesContainer, threshold: 0.5 });
            slides.forEach(s => observer.observe(s));
        }

        function loadProject(project, triggerHistory = true) {
            if (!project) return;
            
            // Mark sidebar active
            projectItems.forEach(item => {
                item.classList.toggle('active-project', item.dataset.projectSlug === project.slug);
            });

            // Update DOM
            projectTitleElement.innerText = project.title;
            slidesContainer.innerHTML = project.images.map(slide => {
                const s = typeof slide === 'object' ? slide : { type: 'image', src: slide, width: 100, position: 'center' };
                const isYT = s.type === 'youtube';
                const width = s.width || 100;
                const align = (s.position === 'left' ? 'flex-start' : (s.position === 'right' ? 'flex-end' : 'center'));

                if (isYT) {
                    return `
                        <div class="slide" style="width: 100%; justify-content: ${align};">
                            <div class="slide-media-container" style="width: ${width}%;">
                                <div class="yt-wrapper">
                                    <iframe src="https://www.youtube.com/embed/${s.src}?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    return `
                        <div class="slide" style="width: 100%; justify-content: ${align};">
                            <div class="slide-media-container" style="width: ${width}%;">
                                <img src="${rootUrl}admin/uploads/${s.src}" alt="${project.title}">
                                ${s.yt_id ? `
                                    <div class="v-overlay" style="top: ${s.yt_top}%; left: ${s.yt_left}%; width: ${s.yt_width}%;">
                                        <div class="yt-wrapper">
                                            <iframe src="https://www.youtube.com/embed/${s.yt_id}?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }
            }).join('');

            // Scroll Top
            slidesContainer.scrollTop = 0;
            slideIndicator.innerText = `1 / ${project.images.length}`;
            setupObserver();

            // Handle URL
            if (triggerHistory) {
                // Ensure pretty URL includes the root path (important for subfolders!)
                const pathSuffix = rootUrl === '/' ? 'work/' : rootUrl.substring(1) + 'work/';
                const fullPrettyPath = '/' + pathSuffix + project.slug;
                history.pushState({ slug: project.slug }, '', fullPrettyPath);
            }

            if (window.innerWidth <= 768) splitLayout.classList.add('show-content');
        }

        // Click interaction
        projectItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const project = projects.find(p => p.slug === item.dataset.projectSlug);
                if (project) loadProject(project);
            });
        });

        // Search
        document.getElementById('sidebarSearch').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            projectItems.forEach(item => {
                const text = item.querySelector('span').innerText.toLowerCase();
                item.style.display = text.includes(query) ? 'flex' : 'none';
            });
        });

        // View Toggles
        document.getElementById('list-toggle').onclick = () => {
            document.getElementById('list-toggle').classList.add('active-toggle');
            document.getElementById('thumb-toggle').classList.remove('active-toggle');
            projectList.classList.remove('thumb-mode');
        };
        document.getElementById('thumb-toggle').onclick = () => {
            document.getElementById('thumb-toggle').classList.add('active-toggle');
            document.getElementById('list-toggle').classList.remove('active-toggle');
            projectList.classList.add('thumb-mode');
        };

        // Popstate & Initial Setup
        window.onpopstate = (e) => {
            if (e.state && e.state.slug) {
                const project = projects.find(p => p.slug === e.state.slug);
                if (project) loadProject(project, false);
            }
        };

        backBtn.onclick = () => splitLayout.classList.remove('show-content');
        setupObserver();
    </script>
</body>
</html>
