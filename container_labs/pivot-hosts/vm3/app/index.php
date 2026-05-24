<?php
require_once 'config.php';
if (is_logged_in()) { header('Location: /dashboard.php'); exit(); }

$error = '';

// GET request → vulnerable (direct SQL interpolation)
// POST request → safe (prepared statement)
if (!empty($_GET['username']) && isset($_GET['password'])) {
    // GET-based login — intentionally vulnerable
    $username = $_GET['username'];
    $password = $_GET['password'];
    $db = get_db();
    $query = "SELECT * FROM users WHERE username='$username' AND password=MD5('$password') AND active=1";
    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_name'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        $db->close();
        header('Location: /dashboard.php');
        exit();
    } else {
        $error = 'Invalid credentials.';
    }
    $db->close();

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST-based login — safe (prepared statement)
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (!empty($username) && !empty($password)) {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM users WHERE username=? AND password=MD5(?) AND active=1");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            $stmt->close();
            $db->close();
            header('Location: /dashboard.php');
            exit();
        } else {
            $error = 'Invalid credentials.';
        }
        $stmt->close();
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>AdminPanel - Secure Login</title>
<link rel="stylesheet" href="/assets/style3.css">
</head>
<body class="login-body">
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon">🛡️</div>
            <div>
                <h1>AdminPanel</h1>
                <span>Security Operations Center</span>
            </div>
        </div>
        <div class="login-form-area">
            <p class="login-subtitle">Enter your credentials to access the SOC dashboard</p>
            <?php if ($error): ?>
            <div class="login-error">
                <span>⚠</span> <?php echo sanitize($error); ?>
            </div>
            <?php endif; ?>
            <form method="POST" action="/index.php" id="login-form">
                <div class="field-group">
                    <label for="username">Username</label>
                    <div class="field-wrap">
                        <span class="field-icon">👤</span>
                        <input type="text" id="username" name="username"
                               placeholder="Enter username" autocomplete="username" required>
                    </div>
                </div>
                <div class="field-group">
                    <label for="password">Password</label>
                    <div class="field-wrap">
                        <span class="field-icon">🔒</span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••" autocomplete="current-password" required>
                    </div>
                </div>
                <button type="submit" class="login-btn" id="login-btn">
                    Sign In →
                </button>
            </form>
        </div>
        <div class="login-footer">
            AdminPanel v<?php echo APP_VERSION; ?> · SOC Platform · Internal Use Only
        </div>
    </div>
</div>
</body>
</html>
