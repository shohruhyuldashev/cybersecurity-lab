<?php
require_once 'config.php';
if (is_logged_in()) redirect('/dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        $db = get_db();
        $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if ($user && $user['password'] === hash_password($password)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['login_time'] = time();
            $db->close();
            redirect('/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - DocuShare</title>
    <meta name="description" content="Sign in to your DocuShare account">
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-brand">
                <i class="fas fa-layer-group"></i>
                <span>DocuShare</span>
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to access your secure document workspace.</p>
            <div class="auth-features">
                <div class="auth-feature">
                    <i class="fas fa-shield-check"></i>
                    <span>End-to-end encrypted</span>
                </div>
                <div class="auth-feature">
                    <i class="fas fa-cloud"></i>
                    <span>Accessible anywhere</span>
                </div>
                <div class="auth-feature">
                    <i class="fas fa-users"></i>
                    <span>Team collaboration</span>
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-card glass">
                <div class="auth-card-header">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to continue</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo sanitize($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login.php" class="auth-form" id="login-form">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username"
                            value="<?php echo sanitize($_POST['username'] ?? ''); ?>"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="input-with-toggle">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="toggle-pass" onclick="togglePass('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember"> Remember me
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="signin-btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="/register.php">Create one</a></p>
                </div>

                <div class="auth-divider">
                    <span>Secured by DocuShare Security Protocol v3</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePass(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
