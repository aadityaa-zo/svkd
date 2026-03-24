<?php
require_once 'config.php';
check_login();

// Handle deletion
if (isset($_GET['delete'])) {
    $fileToDelete = basename($_GET['delete']);
    $filePath = UPLOAD_DIR . $fileToDelete;
    if (file_exists($filePath)) {
        unlink($filePath);
        header('Location: files.php?msg=deleted');
        exit;
    }
}

// Handle upload
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $targetDir = UPLOAD_DIR;
    $fileName = basename($_FILES["file"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf', 'mp4', 'webp');
    if (in_array(strtolower($fileType), $allowTypes)) {
        // Upload file to server
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
            header("Location: files.php?msg=success");
            exit;
        } else {
            $uploadError = "Sorry, there was an error uploading your file.";
        }
    } else {
        $uploadError = "Sorry, only JPG, JPEG, PNG, GIF, PDF, WebP & MP4 files are allowed.";
    }
}

// Get files from uploads
$files = [];
if (is_dir(UPLOAD_DIR)) {
    $scanned = scandir(UPLOAD_DIR);
    if ($scanned !== false) {
        $files = array_diff($scanned, array('.', '..'));
    }
}

include 'header.php';
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-family: 'Outfit'; font-size: 1.5rem; margin-bottom: 0.5rem;">Storage Management</h2>
    <p style="color: var(--text-dim); font-size: 0.9rem;">Upload and manage assets for your portfolio.</p>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
    <div class="alert" style="background: rgba(50, 215, 75, 0.1); color: var(--success); border: 1px solid rgba(50, 215, 75, 0.2); margin-bottom: 1.5rem;">
        File uploaded successfully!
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert" style="background: rgba(255, 69, 58, 0.1); color: var(--error); border: 1px solid rgba(255, 69, 58, 0.2); margin-bottom: 1.5rem;">
        File deleted successfully!
    </div>
<?php endif; ?>

<?php if ($uploadError): ?>
    <div class="alert alert-error"><?= $uploadError ?></div>
<?php endif; ?>

<div class="glass-card" style="margin-bottom: 2.5rem;">
    <h3 style="font-size: 1rem; margin-bottom: 1.25rem; font-weight: 600;">Upload New File</h3>
    <form action="files.php" method="POST" enctype="multipart/form-data">
        <div class="upload-zone" id="uploadZone">
            <p>DRAG & DROP OR CLICK TO BROWSE</p>
            <input type="file" name="file" id="fileInput" style="display: none;" onchange="this.form.submit()">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                Select File
            </button>
        </div>
    </form>
</div>

<div class="data-table-container">
    <table>
        <thead>
            <tr>
                <th>File Name</th>
                <th>Type</th>
                <th>Size</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($files)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-dim);">No files uploaded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($files as $file): 
                    $filePath = UPLOAD_DIR . $file;
                    $fileInfo = pathinfo($filePath);
                    $fileSize = filesize($filePath);
                    $fileDate = date("M d, Y", filemtime($filePath));
                    $isImage = in_array(strtolower($fileInfo['extension']), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                ?>
                <tr>
                    <td>
                        <div class="file-row">
                            <?php if ($isImage): ?>
                                <img src="uploads/<?= $file ?>" class="file-preview" alt="<?= $file ?>">
                            <?php else: ?>
                                <div class="file-preview" style="background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <span><?= $file ?></span>
                        </div>
                    </td>
                    <td><span style="text-transform: uppercase; font-size: 0.75rem; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px;"><?= $fileInfo['extension'] ?></span></td>
                    <td><?= round($fileSize / 1024, 1) ?> KB</td>
                    <td><?= $fileDate ?></td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="uploads/<?= $file ?>" target="_blank" class="btn" style="background: rgba(255,255,255,0.05); color: var(--text-main); padding: 0.4rem 0.8rem;">View</a>
                            <a href="files.php?delete=<?= urlencode($file) ?>" class="btn btn-danger" style="padding: 0.4rem 0.8rem;" onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');

    uploadZone.addEventListener('click', () => fileInput.click());

    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--primary)';
        uploadZone.style.background = 'rgba(255, 255, 255, 0.05)';
    });

    uploadZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--glass-border)';
        uploadZone.style.background = 'transparent';
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        fileInput.files = e.dataTransfer.files;
        fileInput.form.submit();
    });
</script>

<?php include 'footer.php'; ?>
