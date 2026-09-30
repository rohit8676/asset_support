<?php
/**
 * Dashboard Overview Page
 * IT Asset & Support Management System
 */

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Dashboard';

$db = Database::getConnection();
$usersList = $db->query("SELECT id, username, email, full_name, role, status, created_at FROM users ORDER BY id ASC")->fetchAll();
$totalUsers = count($usersList);

$totalCategories = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Dashboard Top Header Bar -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h3 class="fw-bold mb-1">System Dashboard</h3>
        <p class="text-muted mb-0">Overview of registered users, database connectivity, and system status</p>
    </div>
    <div>
        <span class="badge bg-primary px-3 py-2 fs-6">
            <i class="bi bi-database-check me-1"></i> Microsoft SQL Server Connected
        </span>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted small fw-bold text-uppercase">Database Connection</div>
            <div class="fs-4 fw-bold text-primary mt-1"><?= e($dbConfig['database']) ?></div>
            <div class="small text-muted">Host: <code><?= e($dbConfig['host']) ?></code></div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted small fw-bold text-uppercase">Registered Users</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $totalUsers ?> User(s)</div>
            <div class="small text-muted">Authentication: Plain Text Password</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="text-muted small fw-bold text-uppercase">Asset Categories</div>
            <div class="fs-4 fw-bold text-success mt-1"><?= $totalCategories ?> Categories</div>
            <div class="small text-muted"><a href="<?= url('asset_stock.php') ?>" class="text-decoration-none">Manage in Asset &amp; Stock Mgt &rarr;</a></div>
        </div>
    </div>
</div>

<!-- Users Table Card -->
<div class="content-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people-fill text-primary me-2"></i> Users Table (<code>users</code>)</span>
        <span class="badge bg-secondary"><?= $totalUsers ?> Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Username</th>
                    <th>Email Address</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usersList as $u): ?>
                    <tr>
                        <td><code>#<?= $u['id'] ?></code></td>
                        <td class="fw-bold"><?= e($u['username']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['full_name']) ?></td>
                        <td><span class="badge bg-primary-subtle text-primary border"><?= e($u['role']) ?></span></td>
                        <td><span class="badge bg-success"><?= e($u['status']) ?></span></td>
                        <td class="small text-muted"><?= e($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
