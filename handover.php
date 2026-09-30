<?php
/**
 * Employee HandOver & Custody Module Page
 * IT Asset & Support Management System
 */

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'HandOver';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
    <div>
        <h3 class="fw-bold mb-1">Employee HandOver &amp; Custody</h3>
        <p class="text-muted mb-0">Issue assets to staff members, track hardware ownership, and process return condition inspections</p>
    </div>
    <div>
        <span class="badge bg-success px-3 py-2">
            <i class="bi bi-person-check me-1"></i> Custody Tracking
        </span>
    </div>
</div>

<!-- Placeholder Card for HandOver Module -->
<div class="content-card p-5 text-center">
    <div class="d-inline-flex p-4 bg-success bg-opacity-10 text-success rounded-circle mb-3 fs-2">
        <i class="bi bi-person-badge"></i>
    </div>
    <h4 class="fw-bold">HandOver &amp; Returns Module</h4>
    <p class="text-muted col-lg-6 mx-auto mb-4">
        This module will handle hardware assignments to employees, handover receipt generation, asset reassignments, and return inspections.
    </p>
    <a href="<?= url('asset_stock.php') ?>" class="btn btn-outline-primary">
        <i class="bi bi-laptop me-1"></i> Go to Asset &amp; Stock Mgt
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
