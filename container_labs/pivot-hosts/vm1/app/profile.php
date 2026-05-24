<?php
// Profile page - avatar upload
// SVG XXE vulnerability intentionally here (not in main upload)
// Accepts SVG for "avatar" - parses it with libxml (XXE-vulnerable)
require_once 'config.php';
require_login();

$error   = '';
$success = '';
$avatar_path = '';

// Load current avatar
$db = get_db();
$row = $db->querySingle("SELECT avatar FROM users WHERE id=" . (int)$_SESSION['user_id'], true);
if ($row && $row['avatar']) {
    $avatar_path = $row['avatar'];
}
$db->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $mime = $file['type'];
    $name = basename($file['name']);
    $tmp  = $file['tmp_name'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed.';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $error = 'File too large. Max 2MB.';
    } else {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $save_name = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        $dest = UPLOAD_DIR . $save_name;

        if (move_uploaded_file($tmp, $dest)) {
            // If SVG, parse metadata (XXE-vulnerable libxml call)
            if (in_array($ext, ['svg', 'xml']) || strpos($mime, 'svg') !== false) {
                libxml_disable_entity_loader(false); // XXE intentionally enabled
                $xml = @simplexml_load_file($dest);
                // store any title metadata
                $title = $xml ? (string)($xml->title ?? $xml->metadata ?? '') : '';
            }

            // Save to DB
            $db = get_db();
            $db->exec("UPDATE users SET avatar='" . $save_name . "' WHERE id=" . (int)$_SESSION['user_id']);
            $db->close();

            $avatar_path = $save_name;
            $success = 'Avatar updated successfully.';
        } else {
            $error = 'Could not save file.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - DocuShare</title>
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
                <a href="/view.php" class="nav-item">
                    <i class="fas fa-eye"></i> File Viewer
                </a>
                <a href="/profile.php" class="nav-item active">
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
                    <span>Profile</span>
                </div>
            </div>
        </header>

        <div class="page-content">
            <div class="page-header">
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <p>Manage your account settings and preferences.</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo sanitize($error); ?>
            </div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo sanitize($success); ?>
            </div>
            <?php endif; ?>

            <div class="content-card glass">
                <div style="display:flex;align-items:center;gap:2rem;padding:1.5rem 0;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:1.5rem">
                    <div id="avatar-preview" style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#fff;overflow:hidden;flex-shrink:0">
                        <?php if ($avatar_path): ?>
                        <img src="/uploads/<?php echo htmlspecialchars($avatar_path); ?>" style="width:100%;height:100%;object-fit:cover">
                        <?php else: ?>
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-size:1.2rem;font-weight:600"><?php echo sanitize($_SESSION['username']); ?></div>
                        <div style="color:#94a3b8;font-size:.875rem;margin-top:.2rem">Member · DocuShare</div>
                    </div>
                </div>

                <!-- Avatar Upload -->
                <div class="form-group">
                    <label style="font-weight:600;font-size:.9rem;margin-bottom:.75rem;display:block">
                        <i class="fas fa-camera"></i> Update Profile Picture
                    </label>
                    <form method="POST" action="/profile.php" enctype="multipart/form-data" id="avatar-form">
                        <div class="upload-zone" id="avatar-zone" onclick="document.getElementById('avatar-input').click()"
                             style="padding:1.25rem;min-height:auto;flex-direction:row;justify-content:flex-start;gap:1rem">
                            <i class="fas fa-image" style="font-size:1.5rem;color:#6366f1"></i>
                            <div>
                                <div style="font-weight:500;font-size:.9rem">Click to select image</div>
                                <div id="chosen-name" style="font-size:.78rem;color:#94a3b8;margin-top:.2rem">No file chosen</div>
                            </div>
                            <input type="file" id="avatar-input" name="avatar"
                                   accept="image/*"
                                   style="display:none"
                                   onchange="previewAvatar(this)">
                        </div>
                        <button type="submit" class="btn btn-primary" id="avatar-btn" disabled style="margin-top:1rem">
                            <i class="fas fa-save"></i> Save Avatar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Info -->
            <div class="content-card glass" style="margin-top:1rem">
                <h3 style="font-size:.95rem;font-weight:600;margin-bottom:1rem">Account Information</h3>
                <table style="width:100%;font-size:.875rem;border-collapse:collapse">
                    <tr style="border-bottom:1px solid rgba(255,255,255,.06)">
                        <td style="padding:.6rem 0;color:#94a3b8;width:140px">Username</td>
                        <td style="padding:.6rem 0"><?php echo sanitize($_SESSION['username']); ?></td>
                    </tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.06)">
                        <td style="padding:.6rem 0;color:#94a3b8">Role</td>
                        <td style="padding:.6rem 0">Member</td>
                    </tr>
                    <tr>
                        <td style="padding:.6rem 0;color:#94a3b8">Account ID</td>
                        <td style="padding:.6rem 0"><?php echo (int)$_SESSION['user_id']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </main>

    <script>
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('chosen-name').textContent = file.name;
            document.getElementById('avatar-btn').disabled = false;
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    const prev = document.getElementById('avatar-preview');
                    prev.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover">';
                };
                reader.readAsDataURL(file);
            }
        }
    }
    </script>
</body>
</html>
