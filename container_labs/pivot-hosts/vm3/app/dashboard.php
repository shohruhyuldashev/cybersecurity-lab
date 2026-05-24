<?php
require_once 'config.php';
require_login();

$db = get_db();
$users_res = $db->query("SELECT id, username, role, department, email FROM users ORDER BY id");
$users = [];
while ($r = $users_res->fetch_assoc()) $users[] = $r;

$stats = $db->query("SELECT 
    COUNT(*) as total,
    SUM(status='open') as open_cnt,
    SUM(severity='critical') as critical_cnt
    FROM reports")->fetch_assoc();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard - AdminPanel SOC</title>
<link rel="stylesheet" href="/assets/style3.css">
</head>
<body class="app-body">
<div class="layout">
    <!-- SIDEBAR -->
    <aside class="side">
        <div class="side-brand">
            <span class="sb-ico">🛡️</span>
            <span>AdminPanel</span>
        </div>
        <nav class="side-nav">
            <a href="/dashboard.php" class="sn-item active">📊 Dashboard</a>
            <a href="/users.php"     class="sn-item">👥 Users</a>
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

    <!-- MAIN -->
    <main class="content">
        <div class="topbar">
            <h2>Security Operations Dashboard</h2>
            <span class="tb-badge"><?php echo date('D, d M Y H:i'); ?></span>
        </div>

        <div class="pg-body">
            <div class="pg-header">
                <h1>📊 Overview</h1>
                <p>Current security posture and incident tracking.</p>
            </div>

            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#3b82f6">📋</div>
                    <div class="kpi-val"><?php echo $stats['total']; ?></div>
                    <div class="kpi-lbl">Total Reports</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#f59e0b">🔓</div>
                    <div class="kpi-val"><?php echo $stats['open_cnt']; ?></div>
                    <div class="kpi-lbl">Open Issues</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#ef4444">🚨</div>
                    <div class="kpi-val"><?php echo $stats['critical_cnt']; ?></div>
                    <div class="kpi-lbl">Critical</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:#10b981">👥</div>
                    <div class="kpi-val"><?php echo count($users); ?></div>
                    <div class="kpi-lbl">Analysts</div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="panel">
                <div class="panel-hdr">
                    <h3>👥 Analysts — click a row to filter their reports</h3>
                    <a href="/users.php" class="btn-sm-link">View All →</a>
                </div>
                <div class="tbl-wrap">
                    <table class="data-tbl">
                        <thead>
                            <tr>
                                <th>ID</th><th>Username</th><th>Role</th><th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="clickable-row"
                                onclick="window.location='/reports.php?user_id=<?php echo $u['id']; ?>'">
                                <td><?php echo $u['id']; ?></td>
                                <td><strong><?php echo sanitize($u['username']); ?></strong></td>
                                <td><span class="role-tag role-<?php echo $u['role']; ?>"><?php echo sanitize($u['role']); ?></span></td>
                                <td><?php echo sanitize($u['department']); ?></td>
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
