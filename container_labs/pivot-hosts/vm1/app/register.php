<?php
require_once 'config.php';
if (is_logged_in()) redirect('/dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $email    = trim($_POST['email'] ?? '');

    if (empty($username) || empty($password) || empty($email)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 32) {
        $error = 'Username must be 3-32 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = get_db();
        $check = $db->prepare('SELECT id FROM users WHERE username = :username');
        $check->bindValue(':username', $username, SQLITE3_TEXT);
        $res = $check->execute();
        if ($res->fetchArray()) {
            $error = 'Username already taken.';
        } else {
            $stmt = $db->prepare('INSERT INTO users (username, password, email) VALUES (:username, :password, :email)');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':password', hash_password($password), SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            if ($stmt->execute()) {
                $success = 'Account created! You can now sign in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
    <title>Create Account - DocuShare</title>
    <meta name="description" content="Create your DocuShare account">
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
            <h1>Join DocuShare</h1>
            <p>Create your account and start managing documents securely today.</p>
            <div class="auth-features">
                <div class="auth-feature">
                    <i class="fas fa-file-upload"></i>
                    <span>Upload any file type</span>
                </div>
                <div class="auth-feature">
                    <i class="fas fa-share-alt"></i>
                    <span>Easy file sharing</span>
                </div>
                <div class="auth-feature">
                    <i class="fas fa-history"></i>
                    <span>Version history</span>
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-card glass">
                <div class="auth-card-header">
                    <h2>Create Account</h2>
                    <p>Fill in your details to get started</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo sanitize($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo sanitize($success); ?>
                        <a href="/login.php">Sign in now</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/register.php" class="auth-form" id="register-form">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Choose a username"
                            value="<?php echo sanitize($_POST['username'] ?? ''); ?>"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="your@company.com"
                            value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <div class="input-with-toggle">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Min. 6 characters"
                                    autocomplete="new-password"
                                    required
                                >
                                <button type="button" class="toggle-pass" onclick="togglePass('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <div class="input-with-toggle">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    placeholder="Repeat password"
                                    autocomplete="new-password"
                                    required
                                >
                                <button type="button" class="toggle-pass" onclick="togglePass('confirm_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="password-strength" id="pass-strength"></div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="agree" required>
                            I agree to the <a href="#">Terms of Service</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="register-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="/login.php">Sign in</a></p>
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

        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const strength = document.getElementById('pass-strength');
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
            strength.innerHTML = val.length > 0 ? `<div class="strength-bar"><div style="width:${score*25}%;background:${colors[score]}"></div></div><span style="color:${colors[score]}">${labels[score]}</span>` : '';
        });
    </script>
</body>
</html>
