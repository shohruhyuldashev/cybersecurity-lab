<?php
require_once 'config.php';
require_login();
$db = get_db();
$res = $db->query("SELECT id,username,email,role,department,last_login,created_at FROM users ORDER BY id");
$users = [];
while ($r = $res->fetch_assoc()) $users[] = $r;
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Users - AdminPanel SOC</title>
<link rel="stylesheet" href="/assets/style3.css">
</head>
<body class="app-body">
<div class="layout">
    <aside class="side">
        <div class="side-brand"><span class="sb-ico">🛡️</span><span>AdminPanel</span></div>
        <nav class="side-nav">
            <a href="/dashboard.php" class="sn-item">📊 Dashboard</a>
            <a href="/users.php"     class="sn-item active">👥 Users</a>
            <a href="/reports.php"   class="sn-item">📋 Reports</a>
            <a href="/search.php"    class="sn-item">🔍 Search Reports</a>
            <a href="/logout.php"    class="sn-item sn-danger">🚪 Logout</a>
        </nav>
        <div class="side-foot">
            <div class="sf-avatar"><?php echo strtoupper($_SESSION['admin_name'][0]); ?></div>
            <div>
                <div class="sf-name"><?php echo sanitize($_SESSION['admin_name']); ?></div>
                <div class="sf-role"><?php echo sanitize($_SESSION['admin_role']); ?></div>
            </div>
        </div>
    </aside>
    <main class="content">
        <div class="topbar"><h2>Analyst Management</h2></div>
        <div class="pg-body">
            <div class="pg-header">
                <h1>👥 SOC Analysts</h1>
                <p>Click any row to view that analyst's reports.</p>
            </div>
            <div class="panel">
                <div class="tbl-wrap">
                    <table class="data-tbl">
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Department</th><th>Reports</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr class="clickable-row" onclick="window.location='/reports.php?user_id=<?php echo $u['id']; ?>'">
                            <td><?php echo $u['id']; ?></td>
                            <td><strong><?php echo sanitize($u['username']); ?></strong></td>
                            <td><?php echo sanitize($u['email']); ?></td>
                            <td><span class="role-tag role-<?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td>
                            <td><?php echo sanitize($u['department']); ?></td>
                            <td><a href="/reports.php?user_id=<?php echo $u['id']; ?>" class="link-btn">View →</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
