<?php
/**
 * LifeCycle & Recovery Module Page
 * IT Asset & Support Management System
 */

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'LifeCycle & Recovery';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h3 class="fw-bold mb-1">LifeCycle &amp; Recovery</h3>
        <p class="text-muted mb-0">Hardware decommissioning, component harvesting, verification, and asset retirement</p>
    </div>
</div>

<!-- Placeholder Card for LifeCycle & Recovery Module -->
<div class="content-card p-5 text-center">
    <div class="d-inline-flex p-4 bg-warning bg-opacity-10 text-dark rounded-circle mb-3 fs-2">
        <i class="bi bi-arrow-repeat"></i>
    </div>
    <h4 class="fw-bold">LifeCycle &amp; Recovery Management</h4>
    <p class="text-muted col-lg-6 mx-auto mb-4">
        This module will manage computer dismantling workflows, component condition verification, harvested inventory return, and scrap disposal logs.
    </p>
    <a href="<?= url('asset_stock.php') ?>" class="btn btn-outline-primary">
        <i class="bi bi-laptop me-1"></i> Go to Asset &amp; Stock Mgt
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
