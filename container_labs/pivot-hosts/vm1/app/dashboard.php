<?php
require_once 'config.php';
require_login();

$db = get_db();
$stmt = $db->prepare('SELECT * FROM files WHERE user_id = :uid ORDER BY uploaded_at DESC');
$stmt->bindValue(':uid', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$files = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $files[] = $row;
}
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DocuShare</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
</head>
<body class="app-page">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-layer-group"></i>
            <span>DocuShare</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-section-label">Main</span>
                <a href="/dashboard.php" class="nav-item active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="/upload.php" class="nav-item">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Files
                </a>
                <a href="/view.php" class="nav-item">
                    <i class="fas fa-eye"></i> File Viewer
                </a>
            </div>
            <div class="nav-section">
                <span class="nav-section-label">Account</span>
                <a href="/profile.php" class="nav-item">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="/logout.php" class="nav-item nav-danger">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo sanitize($_SESSION['username']); ?></span>
                <span class="user-role">Member</span>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-bar">
            <div class="top-bar-left">
                <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    <span>Home</span> <i class="fas fa-chevron-right"></i> <span>Dashboard</span>
                </div>
            </div>
            <div class="top-bar-right">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search files...">
                </div>
                <button class="notif-btn">
                    <i class="fas fa-bell"></i>
                    <span class="badge">3</span>
                </button>
                <div class="user-menu">
                    <div class="user-avatar-sm">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span><?php echo sanitize($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>

        <div class="page-content">
            <div class="page-header">
                <h1>Welcome back, <?php echo sanitize($_SESSION['username']); ?>!</h1>
                <p>Here's an overview of your document workspace.</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card glass">
                    <div class="stat-icon" style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo count($files); ?></span>
                        <span class="stat-label">Total Files</span>
                    </div>
                </div>
                <div class="stat-card glass">
                    <div class="stat-icon" style="background: linear-gradient(135deg,#0ea5e9,#06b6d4)">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">2.4 GB</span>
                        <span class="stat-label">Storage Used</span>
                    </div>
                </div>
                <div class="stat-card glass">
                    <div class="stat-icon" style="background: linear-gradient(135deg,#10b981,#059669)">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">12</span>
                        <span class="stat-label">Shared Files</span>
                    </div>
                </div>
                <div class="stat-card glass">
                    <div class="stat-icon" style="background: linear-gradient(135deg,#f59e0b,#d97706)">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">48</span>
                        <span class="stat-label">Downloads</span>
                    </div>
                </div>
            </div>

            <!-- Recent Files -->
            <div class="content-card glass">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Recent Files</h2>
                    <a href="/upload.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Upload New
                    </a>
                </div>
                <div class="file-table-wrapper">
                    <?php if (empty($files)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No files yet</h3>
                        <p>Upload your first document to get started.</p>
                        <a href="/upload.php" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Files
                        </a>
                    </div>
                    <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Type</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $f): ?>
                            <tr>
                                <td>
                                    <div class="file-name-cell">
                                        <i class="fas fa-file-image file-icon"></i>
                                        <?php echo sanitize($f['original_name']); ?>
                                    </div>
                                </td>
                                <td><span class="badge-type"><?php echo sanitize($f['file_type']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($f['uploaded_at'])); ?></td>
                                <td>
                                    <a href="/view.php?file=uploads/<?php echo urlencode($f['filename']); ?>" class="btn-action" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/uploads/<?php echo urlencode($f['filename']); ?>" class="btn-action" title="Download" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
