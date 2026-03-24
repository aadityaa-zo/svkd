<?php
require_once 'config.php';
check_login();

$dataFile = dirname(__DIR__) . '/data/projects.json';

// Ensure data directory and file exist
if (!is_dir(dirname($dataFile))) mkdir(dirname($dataFile), 0777, true);
if (!file_exists($dataFile)) file_put_contents($dataFile, json_encode([]));

$projects = json_decode(file_get_contents($dataFile), true);

$message = '';

// Helper function to handle multiple file uploads
function upload_files($filesArray) {
    if (empty($filesArray['name'][0])) return [];
    
    $uploadedNames = [];
    foreach ($filesArray['name'] as $key => $name) {
        if ($filesArray['error'][$key] === 0) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newName = time() . '_' . $key . '.' . $ext;
            if (move_uploaded_file($filesArray['tmp_name'][$key], UPLOAD_DIR . $newName)) {
                $uploadedNames[] = $newName;
            }
        }
    }
    return $uploadedNames;
}

// Handle project deletion
if (isset($_GET['delete'])) {
    $idToDelete = $_GET['delete'];
    $projects = array_filter($projects, function($p) use ($idToDelete) {
        return $p['id'] != $idToDelete;
    });
    file_put_contents($dataFile, json_encode(array_values($projects), JSON_PRETTY_PRINT));
    header('Location: projects.php?msg=deleted');
    exit;
}

// Handle Add/Edit project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $projectId = $_POST['project_id'] ?: time();
    $isEdit = !empty($_POST['project_id']);
    
    // 1. Handle Thumbnail Upload
    $thumbnail = $_POST['existing_thumbnail'];
    if (!empty($_FILES['thumbnail_upload']['name'])) {
        $ext = pathinfo($_FILES['thumbnail_upload']['name'], PATHINFO_EXTENSION);
        $thumbnail = time() . '_thumb.' . $ext;
        move_uploaded_file($_FILES['thumbnail_upload']['tmp_name'], UPLOAD_DIR . $thumbnail);
    }
    
    // 2. Handle New Slide Uploads
    $newSlides = upload_files($_FILES['slides_upload']);
    
    // 3. Process the 'slide_order' JSON
    // This JSON contains the final sequence of slides. 
    // New images are represented by a placeholder like "new_file_0"
    $rawOrder = json_decode($_POST['slide_order'], true);
    $finalSlides = [];
    
    $fileIdx = 0;
    foreach ($rawOrder as $slide) {
        if ($slide['type'] === 'image' && str_starts_with($slide['src'], 'TEMP_')) {
            // Replace placeholder with the actual filename from the upload
            if (isset($newSlides[$fileIdx])) {
                $slide['src'] = $newSlides[$fileIdx];
                $fileIdx++;
                unset($slide['is_new']); // Clean up
                $finalSlides[] = $slide;
            }
        } else {
            $finalSlides[] = $slide;
        }
    }

    $projectData = [
        'id' => $projectId,
        'title' => $_POST['title'],
        'slug' => strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['title'])),
        'thumbnail' => $thumbnail,
        'images' => $finalSlides // Now an array of objects
    ];

    if ($isEdit) {
        foreach ($projects as &$p) {
            if ($p['id'] == $projectId) {
                $p = $projectData;
                break;
            }
        }
    } else {
        $projects[] = $projectData;
    }

    file_put_contents($dataFile, json_encode(array_values($projects), JSON_PRETTY_PRINT));
    $message = $isEdit ? 'Project updated successfully!' : 'Project added successfully!';
    
    // If AJAX request, return success
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Get project for editing
$editProject = null;
if (isset($_GET['edit'])) {
    foreach ($projects as $p) {
        if ($p['id'] == $_GET['edit']) {
            $editProject = $p;
            break;
        }
    }
}

include 'header.php';
?>

<style>
    /* Precision Editor Modal */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.95);
        display: none;
        align-items: center; justify-content: center;
        z-index: 10000;
        backdrop-filter: blur(20px);
        padding: 40px;
    }
    .modal-content {
        background: #111;
        border: 1px solid #333;
        border-radius: var(--radius);
        width: 100%;
        max-width: 1200px;
        position: relative;
        padding: 40px;
    }
    .precision-canvas {
        position: relative;
        width: 100%;
        background: #000;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid #222;
        cursor: crosshair;
    }
    .precision-overlay {
        position: absolute;
        background: rgba(255,0,0,0.4);
        border: 1px solid #ff0000;
        cursor: move;
        display: flex;
        align-items: center; justify-content: center;
    }
    .btn-precision {
        background: rgba(255, 0, 0, 0.1);
        color: #ff0000;
        border: 1px solid rgba(255, 0, 0, 0.2);
        padding: 4px 10px;
        border-radius: 2px;
        font-size: 9px;
        cursor: pointer;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1rem;
    }
    .btn-precision:hover {
        background: #ff0000;
        color: #fff;
    }
    .glass-card {
        border-radius: var(--radius) !important;
        border: 1px solid #333 !important;
        background: #141414 !important;
    }
</style>

<div style="margin-bottom: 4rem;">
    <h2 style="font-family: 'TradeGothicUI', monospace; font-size: 1.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.1rem;">Manage Work</h2>
    <p style="color: var(--text-dim); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05rem;">Add images or YouTube videos to your portfolio slides.</p>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
    <div class="alert" style="background: rgba(50, 215, 75, 0.1); color: var(--success); border: 1px solid rgba(50, 215, 75, 0.2); margin-bottom: 1.5rem;">
        Project saved successfully!
    </div>
<?php endif; ?>

<!-- ADD/EDIT FORM -->
<div class="glass-card" style="margin-bottom: 2.5rem;">
    <h3 style="font-size: 1rem; margin-bottom: 1.25rem; font-weight: 600;">
        <?= $editProject ? 'Edit Project' : 'Add New Work' ?>
    </h3>
    <form action="projects.php" method="POST" enctype="multipart/form-data" id="projectForm">
        <input type="hidden" name="save_project" value="1">
        <input type="hidden" name="project_id" value="<?= $editProject ? $editProject['id'] : '' ?>">
        <input type="hidden" name="existing_thumbnail" value="<?= $editProject ? $editProject['thumbnail'] : '' ?>">
        <input type="hidden" name="slide_order" id="slideOrderInput" value='<?= $editProject ? json_encode($editProject['images']) : "[]" ?>'>

        <div class="form-group">
            <label>Project Title</label>
            <input type="text" name="title" value="<?= $editProject ? htmlspecialchars($editProject['title']) : '' ?>" placeholder="e.g. DIVINE - WALKING ON WATER" required>
        </div>

        <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
                <label>Main Thumbnail</label>
                <div style="display: flex; gap: 1rem; align-items: center; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border: 1px dashed var(--glass-border);">
                    <div id="thumbPreview" style="flex-shrink: 0; width: 120px; height: 80px; border-radius: 6px; background: #222; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid var(--glass-border);">
                        <?php if ($editProject && $editProject['thumbnail']): ?>
                            <img src="uploads/<?= $editProject['thumbnail'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 10px; color: #666;">PREVIEW</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="thumbnail_upload" accept="image/*" class="btn" style="flex: 1; padding: 0.5rem;" onchange="previewThumb(this)">
                </div>
            </div>
            <div>
                <label>Add Media Slides</label>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn" onclick="triggerFileInput()" style="flex: 1; background: rgba(255,255,255,0.05);">Upload Images</button>
                    <button type="button" class="btn" onclick="promptYouTube()" style="flex: 1; background: rgba(255,0,0,0.1); color: #ff5555; border: 1px solid rgba(255,0,0,0.2);">Add YouTube</button>
                </div>
                <input type="file" id="realFileInput" name="slides_upload[]" multiple accept="image/*" style="display: none;" onchange="handleFileSelection(this)">
            </div>
        </div>

        <div class="form-group">
            <label>Workspace (Manage Slides Order & Settings)</label>
            <div id="masterSlideList" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-top: 1rem;">
                <!-- All slides (Existing + Staged) will be injected here -->
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
            <?php if ($editProject): ?>
                <a href="projects.php" class="btn" style="background: rgba(255,255,255,0.05);">Cancel Edit</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" id="publishBtn">
                <?= $editProject ? 'Update Project' : 'Publish Project' ?>
            </button>
        </div>
    </form>
</div>

<!-- DATA TABLE -->
<div class="data-table-container">
    <table>
        <thead>
            <tr>
                <th>Thumbnail</th>
                <th>Project Title</th>
                <th>Slides</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-dim);">No work added yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $p): ?>
                <tr>
                    <td>
                        <img src="uploads/<?= $p['thumbnail'] ?>" class="file-preview" style="width: 120px; height: 68px; object-fit: cover; border-radius: 4px;">
                    </td>
                    <td><span style="font-weight: 500; font-size: 0.95rem;"><?= htmlspecialchars($p['title']) ?></span></td>
                    <td><span style="background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 100px; font-size: 0.75rem;"><?= count($p['images']) ?> items</span></td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="projects.php?edit=<?= $p['id'] ?>" class="btn" style="background: rgba(255,255,255,0.05); color: var(--text-main); padding: 0.4rem 0.8rem;">Edit</a>
                            <a href="projects.php?delete=<?= $p['id'] ?>" class="btn btn-danger" style="padding: 0.4rem 0.8rem;" onclick="return confirm('Delete this project?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // YouTube ID extraction helper
    function getYouTubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }

    const masterSlideList = document.getElementById('masterSlideList');
    const orderInput = document.getElementById('slideOrderInput');
    const thumbPreview = document.getElementById('thumbPreview');
    const projectForm = document.getElementById('projectForm');

    // unifiedState will store both existing slides and newly added one in order
    // Elements will look like:
    // { type: 'image', src: 'filename.jpg' } 
    // { type: 'image', file: FileObject, src: 'TEMP_0', preview: 'blob_url', is_new: true }
    // { type: 'youtube', src: 'videoId', size: 'full', position: 'center' }
    
    let unifiedState = JSON.parse(orderInput.value).map(s => {
        const item = typeof s === 'string' ? { type: 'image', src: s } : s;
        if (!item.width) item.width = 100;
        if (!item.position) item.position = 'center';
        return item;
    });

    // --- CORE SLIDE FUNCTIONS ---
    window.triggerFileInput = function() { document.getElementById('realFileInput').click(); }

    window.previewThumb = function(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => thumbPreview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
            reader.readAsDataURL(input.files[0]);
        }
    }

    window.handleFileSelection = function(input) {
        if (input.files && input.files.length > 0) {
            Array.from(input.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = e => {
                    unifiedState.push({
                        type: 'image',
                        file: file,
                        src: `TEMP_${Date.now()}_${index}`,
                        preview: e.target.result,
                        width: 100,
                        position: 'center',
                        is_new: true
                    });
                    renderMasterList();
                }
                reader.readAsDataURL(file);
            });
            input.value = '';
        }
    }

    window.promptYouTube = function() {
        const url = prompt("Enter YouTube URL");
        if (!url) return;
        const id = getYouTubeId(url);
        if (id) {
            unifiedState.push({
                type: 'youtube',
                src: id,
                width: 100,
                position: 'center'
            });
            renderMasterList();
        }
    }

    function renderMasterList() {
        if (unifiedState.length === 0) {
            masterSlideList.innerHTML = '<p style="color: var(--text-dim); grid-column: 1/-1; text-align: center; padding: 2rem;">No slides yet.</p>';
            return;
        }

        masterSlideList.innerHTML = unifiedState.map((slide, index) => {
            const isYT = slide.type === 'youtube';
            const displaySrc = isYT ? `https://img.youtube.com/vi/${slide.src}/mqdefault.jpg` : (slide.is_new ? slide.preview : `uploads/${slide.src}`);
            
            return `
                <div class="glass-card" style="padding: 0.75rem; border-radius: 12px; border: 1px solid ${slide.is_new ? 'var(--primary-glow)' : 'var(--glass-border)'};">
                    <div style="position: relative; background: #000; border-radius: 6px; overflow: hidden; margin-bottom: 0.75rem;">
                        <div style="position: relative; width: ${slide.width}%; margin: ${slide.position === 'center' ? '0 auto' : (slide.position === 'right' ? '0 0 0 auto' : '0 auto 0 0')};">
                            <img src="${displaySrc}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px; pointer-events: none;">
                            ${slide.yt_id ? `
                                <div class="overlay-drag-box" 
                                    onmousedown="startOverlayDrag(event, ${index})"
                                    style="position: absolute; top: ${slide.yt_top}%; left: ${slide.yt_left}%; width: ${slide.yt_width}%; aspect-ratio: 16/9; background: rgba(255,0,0,0.6); border: 1px solid #fff; cursor: move; z-index: 20; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-size: 8px; color: white; pointer-events: none;">DRAG ME</span>
                                </div>
                            ` : ''}
                         </div>
                        <div style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.8); padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; color: ${isYT ? '#ff0000' : '#00ff88'};">
                            ${slide.type.toUpperCase()}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 0.75rem; background: rgba(255,255,255,0.02); padding: 0.5rem; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                            <label style="font-size: 10px; color: #888;">SLIDE WIDTH: ${slide.width}%</label>
                            <input type="range" min="10" max="100" step="5" value="${slide.width}" 
                                oninput="updateSlide(${index}, 'width', this.value)" 
                                style="width: 60px; height: 4px; accent-color: var(--primary);">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4px; margin-bottom: 0.5rem;">
                            <button type="button" class="btn" onclick="updateSlide(${index}, 'position', 'left')" style="padding: 2px; font-size: 9px; ${slide.position === 'left' ? 'background: var(--primary);' : 'background: #333;'}">LEFT</button>
                            <button type="button" class="btn" onclick="updateSlide(${index}, 'position', 'center')" style="padding: 2px; font-size: 9px; ${slide.position === 'center' ? 'background: var(--primary);' : 'background: #333;'}">MID</button>
                            <button type="button" class="btn" onclick="updateSlide(${index}, 'position', 'right')" style="padding: 2px; font-size: 9px; ${slide.position === 'right' ? 'background: var(--primary);' : 'background: #333;'}">RIGHT</button>
                        </div>

                        ${!isYT ? `
                            <div style="border-top: 1px solid #444; margin-top: 0.5rem; padding-top: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label style="font-size: 9px; color: #00ff88; display: block;">VIDEO OVERLAY</label>
                                    ${slide.yt_id ? `<button type="button" class="btn-precision" onclick="openPrecisionEditor(${index})">Precision Move</button>` : ''}
                                </div>
                                <input type="text" placeholder="YouTube URL for overlay" 
                                    value="${slide.yt_id ? 'https://youtu.be/' + slide.yt_id : ''}"
                                    onchange="updateOverlay(${index}, 'url', this.value)"
                                    style="width: 100%; font-size: 10px; padding: 4px; background: #222; border: 1px solid #444; border-radius: 4px; color: #fff; margin-bottom: 4px;">
                                
                                ${slide.yt_id ? `
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px;">
                                        <div>
                                            <label style="font-size: 8px;">TOP: ${slide.yt_top}%</label>
                                            <input type="range" min="0" max="100" value="${slide.yt_top}" oninput="updateSlide(${index}, 'yt_top', this.value)" style="width:100%;">
                                        </div>
                                        <div>
                                            <label style="font-size: 8px;">LEFT: ${slide.yt_left}%</label>
                                            <input type="range" min="0" max="100" value="${slide.yt_left}" oninput="updateSlide(${index}, 'yt_left', this.value)" style="width:100%;">
                                        </div>
                                        <div style="grid-column: span 2;">
                                            <label style="font-size: 8px;">OVERLAY WIDTH: ${slide.yt_width}%</label>
                                            <input type="range" min="5" max="100" value="${slide.yt_width}" oninput="updateSlide(${index}, 'yt_width', this.value)" style="width:100%;">
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 4px;">
                            <button type="button" class="btn" style="padding: 4px 10px; font-size: 11px; background: rgba(255,255,255,0.05);" onclick="moveItem(${index}, -1)" ${index === 0 ? 'disabled' : ''}>▲</button>
                            <button type="button" class="btn" style="padding: 4px 10px; font-size: 11px; background: rgba(255,255,255,0.05);" onclick="moveItem(${index}, 1)" ${index === unifiedState.length - 1 ? 'disabled' : ''}>▼</button>
                        </div>
                        <button type="button" class="btn btn-danger" style="padding: 4px 10px; font-size: 11px;" onclick="removeItem(${index})">✕</button>
                    </div>
                </div>
            `;
        }).join('');
        
        const cleanOrder = unifiedState.map(s => {
            let item = {...s};
            if (item.preview) delete item.preview;
            if (item.file) delete item.file;
            return item;
        });
        orderInput.value = JSON.stringify(cleanOrder);
    }

    // --- PRECISION EDITOR (MODAL) ---
    let precisionIndex = -1;
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    document.body.appendChild(modal);

    window.openPrecisionEditor = function(index) {
        precisionIndex = index;
        const slide = unifiedState[index];
        const displaySrc = slide.is_new ? slide.preview : `uploads/${slide.src}`;
        
        modal.innerHTML = `
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.25rem; font-family: 'Outfit'; color: #00ff88;">Composition Designer</h3>
                        <p style="color: #666; font-size: 0.8rem; margin: 4px 0 15px 0;">Drag the box to position or use the slider to resize.</p>
                        
                        <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <label style="font-size: 10px; color: #aaa; margin-bottom: 8px; display: block; text-transform: uppercase;">Overlay Width: <span id="pWidthVal">${slide.yt_width || 30}%</span></label>
                            <input type="range" min="5" max="100" value="${slide.yt_width || 30}" 
                                oninput="updatePrecisionWidth(this.value)" 
                                style="width: 250px; height: 5px; accent-color: #00ff88;">
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="closePrecisionEditor()" style="padding: 10px 24px; font-weight: bold;">Save Composition</button>
                </div>
                <div class="precision-canvas" id="pCanvas">
                    <img src="${displaySrc}" style="width: 100%; display: block; filter: brightness(0.7);">
                    <div id="pOverlay" class="precision-overlay" 
                        onmousedown="startOverlayDrag(event, ${index}, true)"
                        style="top: ${slide.yt_top}%; left: ${slide.yt_left}%; width: ${slide.yt_width}%; aspect-ratio: 16/9; z-index: 100;">
                        <span style="color: #fff; font-size: 11px; font-weight: bold; background: rgba(0,0,0,0.5); padding: 4px 8px; border-radius: 4px;">VIDEO PREVIEW</span>
                    </div>
                </div>
            </div>
        `;
        modal.style.display = 'flex';
    }

    window.updatePrecisionWidth = function(val) {
        if (precisionIndex === -1) return;
        unifiedState[precisionIndex].yt_width = parseInt(val);
        document.getElementById('pWidthVal').innerText = val + '%';
        const overlay = document.getElementById('pOverlay');
        if (overlay) overlay.style.width = val + '%';
    }

    window.closePrecisionEditor = function() {
        modal.style.display = 'none';
        precisionIndex = -1;
        renderMasterList();
    }

    // --- DRAG ENGINE ---
    let dragIndex = -1;
    let dragParent = null;
    let isModalDrag = false;

    window.startOverlayDrag = function(e, index, isModal = false) {
        e.preventDefault();
        dragIndex = index;
        isModalDrag = isModal;
        
        if (isModal) {
            dragParent = document.getElementById('pCanvas');
        } else {
            dragParent = e.target.closest('.overlay-drag-box').parentElement;
        }

        window.addEventListener('mousemove', handleOverlayDrag);
        window.addEventListener('mouseup', stopOverlayDrag);
    }

    function handleOverlayDrag(e) {
        if (dragIndex === -1 || !dragParent) return;
        const rect = dragParent.getBoundingClientRect();
        
        let left = ((e.clientX - rect.left) / rect.width) * 100;
        let top = ((e.clientY - rect.top) / rect.height) * 100;
        
        left = Math.max(0, Math.min(100, left));
        top = Math.max(0, Math.min(100, top));
        
        unifiedState[dragIndex].yt_top = Math.round(top);
        unifiedState[dragIndex].yt_left = Math.round(left);
        
        if (isModalDrag) {
            const overlay = document.getElementById('pOverlay');
            if (overlay) {
                overlay.style.top = top + '%';
                overlay.style.left = left + '%';
            }
        } else {
            renderMasterList();
        }
    }

    function stopOverlayDrag() {
        dragIndex = -1;
        dragParent = null;
        window.removeEventListener('mousemove', handleOverlayDrag);
        window.removeEventListener('mouseup', stopOverlayDrag);
        if (!isModalDrag) renderMasterList();
    }

    window.updateOverlay = (index, key, val) => {
        if (key === 'url') {
            const id = getYouTubeId(val);
            if (id) {
                unifiedState[index].yt_id = id;
                if (!unifiedState[index].yt_top) unifiedState[index].yt_top = 20;
                if (!unifiedState[index].yt_left) unifiedState[index].yt_left = 20;
                if (!unifiedState[index].yt_width) unifiedState[index].yt_width = 30;
            } else {
                unifiedState[index].yt_id = null;
            }
        }
        renderMasterList();
    }

    window.updateSlide = (index, key, value) => {
        unifiedState[index][key] = ['width', 'yt_top', 'yt_left', 'yt_width'].includes(key) ? parseInt(value) : value;
        renderMasterList();
    }

    window.moveItem = (index, dir) => {
        const target = index + dir;
        if (target < 0 || target >= unifiedState.length) return;
        [unifiedState[index], unifiedState[target]] = [unifiedState[target], unifiedState[index]];
        renderMasterList();
    }

    window.removeItem = (index) => {
        if (confirm('Delete this slide?')) {
            unifiedState.splice(index, 1);
            renderMasterList();
        }
    }

    projectForm.onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('publishBtn');
        btn.disabled = true;
        btn.innerText = 'Applying Changes...';

        const formData = new FormData(projectForm);
        formData.delete('slides_upload[]');
        unifiedState.forEach(item => {
            if (item.is_new && item.file) {
                formData.append('slides_upload[]', item.file);
            }
        });

        // The hidden 'slideOrderInput' is already updated by renderMasterList
        
        const response = await fetch('projects.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (response.ok) {
            window.location.href = 'projects.php?msg=success';
        } else {
            alert('Save failed.');
            btn.disabled = false;
            btn.innerText = 'Publish Changes';
        }
    }

    // --- INITIALIZE ---
    renderMasterList();
</script>

<?php include 'footer.php'; ?>
