<?php
require_once 'config.php';
require_login();

$error = '';
$success = '';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $original_name = basename($file['name']);
    $tmp_path = $file['tmp_name'];
    $mime_type = $file['type'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error occurred.';
    } elseif ($file['size'] > MAX_FILE_SIZE) {
        $error = 'File too large. Maximum 10MB allowed.';
    } elseif (!in_array($mime_type, $allowed_types)) {
        $error = 'Only image files are allowed.';
    } else {
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $new_name = uniqid('doc_', true) . '.' . $ext;
        $dest = UPLOAD_DIR . $new_name;

        if (move_uploaded_file($tmp_path, $dest)) {
            $db = get_db();
            $stmt = $db->prepare('INSERT INTO files (user_id, filename, original_name, file_type) VALUES (:uid, :fn, :on, :ft)');
            $stmt->bindValue(':uid', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':fn', $new_name, SQLITE3_TEXT);
            $stmt->bindValue(':on', $original_name, SQLITE3_TEXT);
            $stmt->bindValue(':ft', $mime_type, SQLITE3_TEXT);
            $stmt->execute();
            $db->close();
            $success = 'File uploaded successfully! <a href="/view.php?file=uploads/' . urlencode($new_name) . '">View file</a>';
        } else {
            $error = 'Failed to save file.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files - DocuShare</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
</head>
<body class="app-page">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-layer-group"></i>
            <span>DocuShare</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-section-label">Main</span>
                <a href="/dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="/upload.php" class="nav-item active">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Files
                </a>
                <a href="/view.php" class="nav-item">
                    <i class="fas fa-eye"></i> File Viewer
                </a>
                <a href="/profile.php" class="nav-item">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
            </div>
            <div class="nav-section">
                <a href="/logout.php" class="nav-item nav-danger">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            <div class="user-info">
                <span class="user-name"><?php echo sanitize($_SESSION['username']); ?></span>
                <span class="user-role">Member</span>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <div class="top-bar-left">
                <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    <a href="/dashboard.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Upload</span>
                </div>
            </div>
        </header>

        <div class="page-content">
            <div class="page-header">
                <h1><i class="fas fa-cloud-upload-alt"></i> Upload Files</h1>
                <p>Upload images and documents to your workspace.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="content-card glass">
                <form method="POST" action="/upload.php" enctype="multipart/form-data" id="upload-form">
                    <div class="upload-zone" id="upload-zone" onclick="document.getElementById('file-input').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h3>Drag &amp; drop your file here</h3>
                        <p>or click to browse</p>
                        <input
                            type="file"
                            id="file-input"
                            name="file"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            style="display:none"
                            onchange="handleFileSelect(this)"
                        >
                    </div>

                    <div class="file-preview-area" id="file-preview" style="display:none">
                        <div class="selected-file">
                            <i class="fas fa-file" id="file-icon"></i>
                            <div class="file-meta">
                                <span id="file-name">filename</span>
                                <span id="file-size">0 KB</span>
                            </div>
                            <button type="button" onclick="clearFile()" class="clear-file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="upload-btn" disabled style="margin-top:1.5rem">
                        <i class="fas fa-upload"></i> Upload File
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
    const uploadZone = document.getElementById('upload-zone');
    const uploadBtn = document.getElementById('upload-btn');

    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('file-input').files = files;
            handleFileSelect(document.getElementById('file-input'));
        }
    });

    function handleFileSelect(input) {
        if (input.files.length > 0) {
            const file = input.files[0];
            document.getElementById('file-name').textContent = file.name;
            document.getElementById('file-size').textContent = formatSize(file.size);
            document.getElementById('file-preview').style.display = 'flex';
            uploadBtn.disabled = false;
        }
    }

    function clearFile() {
        document.getElementById('file-input').value = '';
        document.getElementById('file-preview').style.display = 'none';
        uploadBtn.disabled = true;
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
        return (bytes/(1024*1024)).toFixed(1) + ' MB';
    }
    </script>
</body>
</html>
