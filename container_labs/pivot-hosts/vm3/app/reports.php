<?php
require_once 'config.php';
require_login();

$db = get_db();

// Get all users for the filter panel
$users_res = $db->query("SELECT id, username FROM users ORDER BY username");
$all_users = [];
while ($r = $users_res->fetch_assoc()) $all_users[] = $r;

// Handle POST search (VULNERABLE: $search directly interpolated)
$search   = '';
$reports  = [];
$filtered_by = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = $_POST['search'] ?? '';

    if (!empty($search)) {
        file_put_contents("/var/www/html/debug.txt", "Raw POST: " . $_POST['search'] . "\nParsed: " . $search . "\n");
        // $search is injected directly into WHERE — SQL injection
        // Normal: WHERE u.username = 'msmith'
        // SQLi:   WHERE u.username = '' UNION SELECT 1,2,3,4,5,6,7,8-- -
        $query = "SELECT r.id, r.title, r.category, r.severity, r.status,
                         r.description, r.created_at, u.username as analyst
                  FROM reports r
                  JOIN users u ON r.user_id = u.id
                  WHERE u.username = '$search'";
        $res = $db->query($query);
        if ($res && $res !== true) {
            while ($row = $res->fetch_assoc()) $reports[] = $row;
        }
        $filtered_by = $search;
    }
} else {
    // GET: load all reports (safe)
    $query = "SELECT r.id, r.title, r.category, r.severity, r.status,
                     r.description, r.created_at, u.username as analyst
              FROM reports r
              JOIN users u ON r.user_id = u.id
              ORDER BY r.created_at DESC";
    $res = $db->query($query);
    if ($res) while ($row = $res->fetch_assoc()) $reports[] = $row;
}

$db->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reports - AdminPanel SOC</title>
<link rel="stylesheet" href="/assets/style3.css">
</head>
<body class="app-body">
<div class="layout">
    <aside class="side">
        <div class="side-brand"><span class="sb-ico">🛡️</span><span>AdminPanel</span></div>
        <nav class="side-nav">
            <a href="/dashboard.php" class="sn-item">📊 Dashboard</a>
            <a href="/users.php"     class="sn-item">👥 Users</a>
            <a href="/reports.php"   class="sn-item active">📋 Reports</a>
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
        <div class="topbar">
            <h2>Incident Reports</h2>
        </div>
        <div class="pg-body">
            <div class="pg-header">
                <h1>📋 Reports
                    <?php if ($filtered_by): ?>
                    <span class="filter-badge">Filtered: <?php echo sanitize($filtered_by); ?></span>
                    <?php endif; ?>
                </h1>
                <p>Click an analyst name to filter their reports.</p>
            </div>

            <!-- Hidden POST form — submitted by JS when clicking a username -->
            <form method="POST" action="/reports.php" id="filter-form" style="display:none">
                <input type="text" name="search" id="filter-search-val">
            </form>

            <!-- Analyst Filter Panel -->
            <div class="panel" style="margin-bottom:1.25rem">
                <div class="panel-hdr"><h3>👥 Analyst Filter</h3>
                    <?php if ($filtered_by): ?>
                    <a href="/reports.php" class="btn-sm-link">Clear filter</a>
                    <?php endif; ?>
                </div>
                <div style="padding:.85rem 1.25rem;display:flex;flex-wrap:wrap;gap:.5rem">
                    <?php foreach ($all_users as $u): ?>
                    <button type="button"
                            onclick="filterByUser('<?php echo htmlspecialchars($u['username']); ?>')"
                            class="analyst-pill <?php echo ($filtered_by === $u['username']) ? 'active' : ''; ?>">
                        <?php echo sanitize($u['username']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="panel">
                <div class="panel-hdr">
                    <h3>Found <?php echo count($reports); ?> report(s)</h3>
                </div>
                <div class="tbl-wrap">
                    <table class="data-tbl">
                        <thead>
                            <tr>
                                <th>#</th><th>Title</th><th>Category</th>
                                <th>Severity</th><th>Status</th><th>Analyst</th><th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($reports)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:2rem">
                            No reports found.
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($reports as $rep): ?>
                        <tr class="clickable-row" onclick="toggleDetail(<?php echo $rep['id']; ?>)">
                            <td><?php echo $rep['id']; ?></td>
                            <td><strong><?php echo sanitize($rep['title']); ?></strong></td>
                            <td><?php echo sanitize($rep['category']); ?></td>
                            <td><span class="sev-tag sev-<?php echo $rep['severity']; ?>"><?php echo $rep['severity']; ?></span></td>
                            <td><span class="status-tag st-<?php echo str_replace('_','-',$rep['status']); ?>"><?php echo $rep['status']; ?></span></td>
                            <td><?php echo sanitize($rep['analyst']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($rep['created_at'])); ?></td>
                        </tr>
                        <tr id="detail-<?php echo $rep['id']; ?>" class="detail-row" style="display:none">
                            <td colspan="7">
                                <div class="detail-box">
                                    <strong>Description:</strong>
                                    <p><?php echo sanitize($rep['description']); ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
function filterByUser(username) {
    // POST request with search=username in body — SQLi endpoint
    document.getElementById('filter-search-val').value = username;
    document.getElementById('filter-form').submit();
}
function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>
</body>
</html>
