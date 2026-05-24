<?php
require_once 'config.php';
require_login();

$search = $_POST['search'] ?? '';
$results = [];

if (!empty($search)) {
    $db = get_db();
    // Safe: prepared statement with bound parameter
    $stmt = $db->prepare(
        "SELECT r.id, r.title, r.category, r.severity, r.status,
                r.description, r.created_at, u.username
         FROM reports r JOIN users u ON r.user_id = u.id
         WHERE r.title LIKE ? OR r.description LIKE ?
         ORDER BY r.created_at DESC"
    );
    $like = '%' . $search . '%';
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) while ($row = $res->fetch_assoc()) $results[] = $row;
    $stmt->close();
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Search - AdminPanel SOC</title>
<link rel="stylesheet" href="/assets/style3.css">
</head>
<body class="app-body">
<div class="layout">
    <aside class="side">
        <div class="side-brand"><span class="sb-ico">🛡️</span><span>AdminPanel</span></div>
        <nav class="side-nav">
            <a href="/dashboard.php" class="sn-item">📊 Dashboard</a>
            <a href="/users.php"     class="sn-item">👥 Users</a>
            <a href="/reports.php"   class="sn-item">📋 Reports</a>
            <a href="/search.php"    class="sn-item active">🔍 Search Reports</a>
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
        <div class="topbar"><h2>Report Search</h2></div>
        <div class="pg-body">
            <div class="pg-header">
                <h1>🔍 Search Reports</h1>
                <p>Search through incident reports by keyword.</p>
            </div>

            <div class="panel">
                <div class="panel-hdr"><h3>Search Query</h3></div>
                <div style="padding:1.1rem 1.25rem">
                    <form method="POST" action="/search.php">
                        <div class="sf-row">
                            <input type="text" name="search" id="search-q"
                                   value="<?php echo sanitize($search); ?>"
                                   placeholder="Search reports..."
                                   class="sf-input" style="flex:1">
                            <button type="submit" class="btn-search">🔍 Search</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($search)): ?>
            <div class="panel">
                <div class="panel-hdr"><h3>Results (<?php echo count($results); ?>)</h3></div>
                <div class="tbl-wrap">
                    <table class="data-tbl">
                        <thead>
                            <tr><th>#</th><th>Title</th><th>Category</th><th>Severity</th><th>Analyst</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($results)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:2rem;color:#475569">No results found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?php echo $r['id']; ?></td>
                            <td><?php echo sanitize($r['title']); ?></td>
                            <td><?php echo sanitize($r['category']); ?></td>
                            <td><span class="sev-tag sev-<?php echo $r['severity']; ?>"><?php echo $r['severity']; ?></span></td>
                            <td><?php echo sanitize($r['username']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
